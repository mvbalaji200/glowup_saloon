<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BookingConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_and_concurrency_conflict(): void
    {
        // Insert test seed data directly into DB
        $branchId = DB::table('branches')->insertGetId([
            'name'       => 'Kakkanad',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $serviceId = DB::table('services')->insertGetId([
            'name'             => 'Haircut',
            'duration_minutes' => 30,
            'price'            => 500,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $stylistId = DB::table('stylists')->insertGetId([
            'name'       => 'Anu',
            'branch_id'  => $branchId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $time = '2026-10-25 11:00:00';

        $payload = [
            'customer_phone' => '9876543210',
            'branch_id'      => $branchId,
            'service_id'     => $serviceId,
            'stylist_id'     => $stylistId,
            'starts_at'      => $time,
        ];

        // 1. First booking succeeds
        $res1 = $this->postJson('/api/bookings', $payload);
        $res1->assertStatus(201);
        $bookingId = $res1->json('data.id');

        // 2. Duplicate booking fails with 409
        $res2 = $this->postJson('/api/bookings', $payload);
        $res2->assertStatus(409);
        $res2->assertJson(['message' => 'This slot is already taken.']);

        // 3. Cancel endpoint frees the booking
        $res3 = $this->postJson("/api/bookings/{$bookingId}/cancel");
        $res3->assertStatus(200);
        $this->assertEquals('cancelled', $res3->json('data.status'));
    }
}

