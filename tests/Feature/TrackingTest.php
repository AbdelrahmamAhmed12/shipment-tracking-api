<?php

namespace Tests\Feature;

use App\Models\Shipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_anyone_can_track_a_shipment_without_seeing_private_details(): void
    {
        $shipment = Shipment::factory()->create();

        $this->getJson("/api/v1/track/{$shipment->tracking_number}")
            ->assertOk()
            ->assertJsonPath('data.tracking_number', $shipment->tracking_number)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonMissingPath('data.receiver')
            ->assertJsonMissingPath('data.shipping_fee');
    }

    public function test_an_unknown_tracking_number_returns_not_found(): void
    {
        $this->getJson('/api/v1/track/ST0000000000')->assertNotFound();
    }
}
