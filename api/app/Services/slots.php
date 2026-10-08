<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Service;
use App\Models\Stylist;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SlotService
{
    public function getAvailableSlots(int $branchId, int $serviceId, string $dateString): Collection
    {
        $timezone = config('app.timezone', 'Asia/Kolkata');
        $now = Carbon::now($timezone);
        $targetDate = Carbon::createFromFormat('Y-m-d', $dateString, $timezone);

        $branch = Branch::findOrFail($branchId);
        $service = Service::findOrFail($serviceId);
        $openTime = Carbon::parse($branch->opening_time, $timezone)->setDateFrom($targetDate);
        $closeTime = Carbon::parse($branch->closing_time, $timezone)->setDateFrom($targetDate);
        
        $duration = (int) $service->duration_minutes; /
        $stylists = Stylist::where('branch_id', $branchId)
            ->where('is_active', true)
            ->get();

        $output = collect();

        foreach ($stylists as $stylist) {
            $slots = [];
            $currentTime = $openTime->copy();

            $existingBookings = Booking::where('stylist_id', $stylist->id)
                ->whereDate('starts_at', $targetDate->toDateString())
                ->where('status', '!=', 'cancelled')
                ->get();

            while ($currentTime->copy()->addMinutes($duration)->lte($closeTime)) {
                $slotStart = $currentTime->copy();
                $slotEnd = $currentTime->copy()->addMinutes($duration);

                // Skip past slots
                if ($slotStart->lte($now)) {
                    $currentTime->addMinutes(30);
                    continue;
                }

                // FIXME: Getting collision check bugs when service duration is 60+ mins
                // How to check if ANY 30-min block inside $slotStart -> $slotEnd collides?
                $isBooked = false;
                foreach ($existingBookings as $booking) {
                    $bStart = Carbon::parse($booking->starts_at, $timezone);
                    $bEnd = Carbon::parse($booking->ends_at, $timezone);
     if ($slotStart->equalTo($bStart)) {
                        $isBooked = true;
                        break;
                    }
                }

                if (!$isBooked) {
                    $slots[] = [
                        'starts_at' => $slotStart->toIso8601String(),
                        'ends_at'   => $slotEnd->toIso8601String(),
                        'time_label'=> $slotStart->format('g:i A'),
                    ];
                }

                $currentTime->addMinutes(30);
            }

            $output->push([
                'stylist_id'   => $stylist->id,
                'stylist_name' => $stylist->name,
                'slots'        => $slots,
            ]);
        }

        return $output;
    }
}
