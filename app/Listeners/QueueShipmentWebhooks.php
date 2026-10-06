<?php

namespace App\Listeners;

use App\Events\ShipmentStatusChanged;
use App\Jobs\SendWebhook;
use App\Models\WebhookEndpoint;

class QueueShipmentWebhooks
{
    public const EVENT = 'shipment.status_changed';

    /**
     * Record one delivery per active endpoint of the shipment owner and
     * hand each one to the queue.
     */
    public function handle(ShipmentStatusChanged $event): void
    {
        $shipment = $event->shipment;

        $payload = [
            'event' => self::EVENT,
            'tracking_number' => $shipment->tracking_number,
            'reference' => $shipment->reference,
            'from_status' => $event->from?->value,
            'to_status' => $event->to->value,
            'occurred_at' => now()->toIso8601String(),
        ];

        WebhookEndpoint::query()
            ->where('user_id', $shipment->user_id)
            ->where('is_active', true)
            ->get()
            ->each(function (WebhookEndpoint $endpoint) use ($shipment, $payload) {
                $delivery = $endpoint->deliveries()->create([
                    'shipment_id' => $shipment->id,
                    'event' => self::EVENT,
                    'payload' => $payload,
                ]);

                SendWebhook::dispatch($delivery);
            });
    }
}
