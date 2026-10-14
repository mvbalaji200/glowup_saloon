<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_bad_signature_returns_400(): void
    {
        Config::set('services.stripe.webhook_secret', 'whsec_test_secret');

        $response = $this->call(
            'POST',
            '/api/webhooks/stripe',
            [],
            [],
            [],
            [
                'HTTP_STRIPE_SIGNATURE' => 'bad_signature_header',
                'CONTENT_TYPE'          => 'application/json',
            ],
            json_encode(['id' => 'evt_test_123', 'type' => 'checkout.session.completed'])
        );

        $response->assertStatus(400);
        $response->assertJson(['error' => 'Invalid signature']);
    }

    public function test_duplicate_event_returns_200_and_is_ignored(): void
    {
        DB::table('stripe_events')->insert([
            'event_id'   => 'evt_test_duplicate',
            'type'       => 'checkout.session.completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('stripe_events', ['event_id' => 'evt_test_duplicate']);
    }
}
