<?php
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Stylist;
use App\Models\Service;
use App\Models\Branch;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// 1. GET /api/slots (GS-9: Free slots per stylist in 1 query)
Route::get('/slots', function (Request $request) {
    $request->validate([
        'branch_id'  => 'required|integer',
        'service_id' => 'required|integer',
        'date'       => 'required|date_format:Y-m-d',
    ]);

    $service = Service::findOrFail($request->service_id);
    $duration = $service->duration_minutes ?? 30;
    $date = $request->date;
    $now = now('Asia/Kolkata');

    $stylists = Stylist::where('branch_id', $request->branch_id)->get();
    
    // Single query for all active bookings across all stylists today
    $bookings = Booking::whereIn('stylist_id', $stylists->pluck('id'))
        ->where('status', '!=', 'cancelled')
        ->whereDate('starts_at', $date)
        ->get();

    $data = [];

    foreach ($stylists as $stylist) {
        $slots = [];
        $current = Carbon::parse("{$date} 09:00:00", 'Asia/Kolkata');
        $close   = Carbon::parse("{$date} 21:00:00", 'Asia/Kolkata');

        while ($current->copy()->addMinutes($duration)->lte($close)) {
            $slotEnd = $current->copy()->addMinutes($duration);

            // Never show past slots
            if ($current->lte($now)) {
                $current->addMinutes(30);
                continue;
            }

            // Check if slot overlaps with an existing booking
            $isTaken = $bookings->where('stylist_id', $stylist->id)->contains(function ($b) use ($current, $slotEnd) {
                return $current->lt(Carbon::parse($b->ends_at)) && $slotEnd->gt(Carbon::parse($b->starts_at));
            });

            if (!$isTaken) {
                $slots[] = $current->toDateTimeString();
            }

            $current->addMinutes(30);
        }

        $data[] = [
            'stylist_id'   => $stylist->id,
            'stylist_name' => $stylist->name,
            'slots'        => $slots,
        ];
    }

    return response()->json(['data' => $data]);
});

// 2. POST /api/bookings (GS-10: Booking with transaction & 409 guard)
Route::post('/bookings', function (Request $request) {
    $data = $request->validate([
        'customer_phone' => 'required',
        'branch_id'      => 'required|integer',
        'service_id'     => 'required|integer',
        'stylist_id'     => 'required|integer',
        'starts_at'      => 'required|date_format:Y-m-d H:i:s',
    ]);

    return DB::transaction(function () use ($data) {
        $service = Service::findOrFail($data['service_id']);
        $startsAt = Carbon::parse($data['starts_at'], 'Asia/Kolkata');
        $endsAt = $startsAt->copy()->addMinutes($service->duration_minutes ?? 30);

        // Concurrency lock check
        $exists = Booking::where('stylist_id', $data['stylist_id'])
            ->where('status', '!=', 'cancelled')
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->lockForUpdate()
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'This slot is already taken.'], 409);
        }

        $customer = Customer::firstOrCreate(
            ['phone' => $data['customer_phone']],
            ['name' => 'Customer', 'is_active' => true]
        );

        $booking = Booking::create([
            'customer_id' => $customer->id,
            'branch_id'   => $data['branch_id'],
            'service_id'  => $data['service_id'],
            'stylist_id'  => $data['stylist_id'],
            'starts_at'   => $startsAt,
            'ends_at'     => $endsAt,
            'status'      => 'confirmed',
        ]);

        // Simulated SMS log
        $branch = Branch::find($data['branch_id']);
        $branchName = $branch ? $branch->name : 'GlowUp';
        DB::table('sms_logs')->insert([
            'phone'      => $customer->phone,
            'message'    => "Hi {$customer->name}, your {$service->name} at {$branchName} is booked for " . $startsAt->format('g:i A, j M') . ".",
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['data' => $booking], 201);
    });
});

// 3. POST /api/bookings/{booking}/cancel (GS-10: Soft cancel)
Route::post('/bookings/{booking}/cancel', function (Booking $booking) {
    $booking->update(['status' => 'cancelled']);
    return response()->json(['data' => $booking]);
});
