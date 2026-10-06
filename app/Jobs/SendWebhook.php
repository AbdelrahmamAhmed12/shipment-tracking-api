<?php

namespace App\Jobs;

use App\Models\WebhookDelivery;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Throwable;

class SendWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(public WebhookDelivery $delivery) {}

    /**
     * Seconds to wait before each retry.
     *
     * @return list<int>
     */
    public function backoff(): array
    {
        return [10, 60, 300, 900];
    }

    public function handle(): void
    {
        $delivery = $this->delivery;
        $endpoint = $delivery->endpoint;

        // The endpoint was removed or switched off after the job was queued.
        if ($endpoint === null || ! $endpoint->is_active) {
            return;
        }

        $body = json_encode($delivery->payload, JSON_THROW_ON_ERROR);

        $delivery->increment('attempts');

        $response = Http::timeout(10)
            ->withHeaders([
                'X-Webhook-Event' => $delivery->event,
                'X-Webhook-Delivery' => (string) $delivery->id,
                'X-Webhook-Signature' => self::sign($body, $endpoint->secret),
            ])
            ->withBody($body, 'application/json')
            ->post($endpoint->url);

        $delivery->update(['response_status' => $response->status()]);

        // A non-2xx answer throws, which sends the job back for a retry.
        $response->throw();

        $delivery->update(['delivered_at' => now(), 'last_error' => null]);
    }

    public function failed(?Throwable $exception): void
    {
        $this->delivery->update([
            'last_error' => $exception ? mb_substr($exception->getMessage(), 0, 500) : 'Unknown error',
        ]);
    }

    /**
     * Receivers recompute this value from the raw body to verify the sender.
     */
    public static function sign(string $body, string $secret): string
    {
        return hash_hmac('sha256', $body, $secret);
    }
}
