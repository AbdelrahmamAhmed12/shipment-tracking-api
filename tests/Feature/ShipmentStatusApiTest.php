<?php

namespace Tests\Feature;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShipmentStatusApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_client_cannot_change_a_shipment_status(): void
    {
        $client = User::factory()->create();
        $shipment = Shipment::factory()->for($client)->create();

        Sanctum::actingAs($client);

        $this->patchJson("/api/v1/shipments/{$shipment->tracking_number}/status", ['status' => 'picked_up'])
            ->assertForbidden();

        $this->assertSame(ShipmentStatus::Pending, $shipment->fresh()->status);
    }

    public function test_an_admin_can_move_a_shipment_to_an_allowed_status(): void
    {
        $admin = User::factory()->admin()->create();
        $shipment = Shipment::factory()->create();

        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/shipments/{$shipment->tracking_number}/status", [
            'status' => 'picked_up',
            'note' => 'Collected from the warehouse',
        ])->assertOk()->assertJsonPath('data.status', 'picked_up');

        $this->assertDatabaseHas('shipment_status_logs', [
            'shipment_id' => $shipment->id,
            'from_status' => 'pending',
            'to_status' => 'picked_up',
            'changed_by' => $admin->id,
            'note' => 'Collected from the warehouse',
        ]);
    }

    public function test_a_transition_that_skips_steps_is_refused(): void
    {
        $shipment = Shipment::factory()->create();

        Sanctum::actingAs(User::factory()->admin()->create());

        $this->patchJson("/api/v1/shipments/{$shipment->tracking_number}/status", ['status' => 'delivered'])
            ->assertConflict()
            ->assertJsonPath('allowed', ['picked_up', 'cancelled']);

        $this->assertSame(ShipmentStatus::Pending, $shipment->fresh()->status);
        $this->assertDatabaseCount('shipment_status_logs', 0);
    }

    public function test_a_delivered_shipment_cannot_change_again(): void
    {
        $shipment = Shipment::factory()->status(ShipmentStatus::Delivered)->create();

        Sanctum::actingAs(User::factory()->admin()->create());

        $this->patchJson("/api/v1/shipments/{$shipment->tracking_number}/status", ['status' => 'returned'])
            ->assertConflict();
    }

    public function test_delivery_records_the_delivery_time(): void
    {
        $shipment = Shipment::factory()->status(ShipmentStatus::OutForDelivery)->create();

        Sanctum::actingAs(User::factory()->admin()->create());

        $this->patchJson("/api/v1/shipments/{$shipment->tracking_number}/status", ['status' => 'delivered'])
            ->assertOk();

        $this->assertNotNull($shipment->fresh()->delivered_at);
    }

    public function test_an_unknown_status_fails_validation(): void
    {
        $shipment = Shipment::factory()->create();

        Sanctum::actingAs(User::factory()->admin()->create());

        $this->patchJson("/api/v1/shipments/{$shipment->tracking_number}/status", ['status' => 'teleported'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }
}
