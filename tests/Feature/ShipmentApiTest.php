<?php

namespace Tests\Feature;

use App\Models\Shipment;
use App\Models\User;
use App\Models\Zone;
use App\Models\ZoneRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShipmentApiTest extends TestCase
{
    use RefreshDatabase;

    private ZoneRate $rate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rate = ZoneRate::factory()->create([
            'base_weight_grams' => 5000,
            'base_price' => 2500,
            'extra_kg_price' => 200,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'reference' => 'ORDER-1001',
            'origin_zone_id' => $this->rate->origin_zone_id,
            'destination_zone_id' => $this->rate->destination_zone_id,
            'receiver_name' => 'Sara Ali',
            'receiver_phone' => '+966500000000',
            'receiver_address' => 'King Fahd Road, Riyadh',
            'weight_grams' => 7000,
            ...$overrides,
        ];
    }

    public function test_guests_cannot_create_shipments(): void
    {
        $this->postJson('/api/v1/shipments', $this->payload())->assertUnauthorized();
    }

    public function test_a_client_can_create_a_shipment(): void
    {
        $client = User::factory()->create();
        Sanctum::actingAs($client);

        $response = $this->postJson('/api/v1/shipments', $this->payload());

        $response->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.reference', 'ORDER-1001')
            ->assertJsonPath('data.shipping_fee', 2900)
            ->assertJsonPath('data.currency', 'SAR')
            ->assertJsonPath('data.cod_amount', 0)
            ->assertJsonCount(1, 'data.history');

        $this->assertStringStartsWith('ST', $response->json('data.tracking_number'));

        $shipment = Shipment::query()->sole();
        $this->assertSame($client->id, $shipment->user_id);
        $this->assertDatabaseHas('shipment_status_logs', [
            'shipment_id' => $shipment->id,
            'from_status' => null,
            'to_status' => 'pending',
            'changed_by' => $client->id,
        ]);
    }

    public function test_the_same_reference_cannot_be_used_twice_by_one_client(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/shipments', $this->payload())->assertCreated();
        $this->postJson('/api/v1/shipments', $this->payload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reference');

        $this->assertDatabaseCount('shipments', 1);
    }

    public function test_two_clients_can_use_the_same_reference(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/v1/shipments', $this->payload())->assertCreated();

        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/v1/shipments', $this->payload())->assertCreated();

        $this->assertDatabaseCount('shipments', 2);
    }

    public function test_weight_above_the_limit_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/shipments', $this->payload(['weight_grams' => 70001]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('weight_grams');
    }

    public function test_a_shipment_between_zones_without_a_rate_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/shipments', $this->payload([
            'destination_zone_id' => Zone::factory()->create()->id,
        ]))->assertUnprocessable();

        $this->assertDatabaseCount('shipments', 0);
    }

    public function test_a_client_only_lists_their_own_shipments(): void
    {
        $client = User::factory()->create();
        Shipment::factory()->count(2)->for($client)->create();
        Shipment::factory()->create();

        Sanctum::actingAs($client);

        $this->getJson('/api/v1/shipments')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_an_admin_lists_all_shipments_and_can_filter_by_status(): void
    {
        Shipment::factory()->count(2)->create();
        Shipment::factory()->create(['status' => 'delivered']);

        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson('/api/v1/shipments')->assertOk()->assertJsonCount(3, 'data');
        $this->getJson('/api/v1/shipments?status=delivered')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_a_client_cannot_view_another_clients_shipment(): void
    {
        $shipment = Shipment::factory()->create();

        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/v1/shipments/{$shipment->tracking_number}")->assertForbidden();
    }

    public function test_a_client_can_view_their_own_shipment(): void
    {
        $client = User::factory()->create();
        $shipment = Shipment::factory()->for($client)->create();

        Sanctum::actingAs($client);

        $this->getJson("/api/v1/shipments/{$shipment->tracking_number}")
            ->assertOk()
            ->assertJsonPath('data.tracking_number', $shipment->tracking_number);
    }

    public function test_a_client_can_get_a_price_quote(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/rates/quote', [
            'origin_zone_id' => $this->rate->origin_zone_id,
            'destination_zone_id' => $this->rate->destination_zone_id,
            'weight_grams' => 1000,
        ])->assertOk()->assertJsonPath('data.shipping_fee', 2500);
    }
}
