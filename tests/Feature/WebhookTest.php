<?php

namespace Tests\Feature;

use App\Jobs\SendWebhook;
use App\Listeners\QueueShipmentWebhooks;
use App\Models\Shipment;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_status_change_sends_a_signed_webhook_to_the_client(): void
    {
        Http::fake();

        $client = User::factory()->create();
        $endpoint = WebhookEndpoint::factory()->for($client)->create();
        $shipment = Shipment::factory()->for($client)->create();

        Sanctum::actingAs(User::factory()->admin()->create());

        $this->patchJson("/api/v1/shipments/{$shipment->tracking_number}/status", ['status' => 'picked_up'])
            ->assertOk();

        Http::assertSent(function (Request $request) use ($endpoint, $shipment) {
            return $request->url() === $endpoint->url
                && $request['tracking_number'] === $shipment->tracking_number
                && $request['from_status'] === 'pending'
                && $request['to_status'] === 'picked_up'
                && $request->hasHeader('X-Webhook-Signature', SendWebhook::sign($request->body(), 'test-secret'));
        });

        $delivery = WebhookDelivery::query()->sole();
        $this->assertSame(200, $delivery->response_status);
        $this->assertSame(1, $delivery->attempts);
        $this->assertNotNull($delivery->delivered_at);
    }

    public function test_inactive_endpoints_and_other_clients_receive_nothing(): void
    {
        Http::fake();

        $client = User::factory()->create();
        WebhookEndpoint::factory()->for($client)->create(['is_active' => false]);
        WebhookEndpoint::factory()->create();
        $shipment = Shipment::factory()->for($client)->create();

        Sanctum::actingAs(User::factory()->admin()->create());

        $this->patchJson("/api/v1/shipments/{$shipment->tracking_number}/status", ['status' => 'picked_up'])
            ->assertOk();

        Http::assertNothingSent();
        $this->assertDatabaseCount('webhook_deliveries', 0);
    }

    public function test_a_failed_delivery_is_recorded_and_thrown_for_retry(): void
    {
        Http::fake(['*' => Http::response('Server error', 500)]);

        $client = User::factory()->create();
        $endpoint = WebhookEndpoint::factory()->for($client)->create();
        $shipment = Shipment::factory()->for($client)->create();

        $delivery = $endpoint->deliveries()->create([
            'shipment_id' => $shipment->id,
            'event' => QueueShipmentWebhooks::EVENT,
            'payload' => ['tracking_number' => $shipment->tracking_number],
        ]);

        try {
            (new SendWebhook($delivery))->handle();
            $this->fail('A failed webhook must throw so the queue retries it.');
        } catch (RequestException) {
            // Expected.
        }

        $delivery->refresh();
        $this->assertSame(500, $delivery->response_status);
        $this->assertSame(1, $delivery->attempts);
        $this->assertNull($delivery->delivered_at);
    }

    public function test_a_client_registers_an_endpoint_and_sees_the_secret_once(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/webhook-endpoints', ['url' => 'https://client.example.com/hook'])
            ->assertCreated()
            ->assertJsonStructure(['data' => ['id', 'url', 'is_active'], 'secret']);

        $this->getJson('/api/v1/webhook-endpoints')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonMissingPath('data.0.secret')
            ->assertJsonMissingPath('secret');
    }

    public function test_webhook_urls_must_use_https(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/webhook-endpoints', ['url' => 'http://client.example.com/hook'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('url');
    }

    public function test_a_client_cannot_delete_another_clients_endpoint(): void
    {
        $endpoint = WebhookEndpoint::factory()->create();

        Sanctum::actingAs(User::factory()->create());

        $this->deleteJson("/api/v1/webhook-endpoints/{$endpoint->id}")->assertNotFound();
        $this->assertDatabaseCount('webhook_endpoints', 1);
    }
}
