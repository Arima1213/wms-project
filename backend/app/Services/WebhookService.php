<?php

namespace App\Services;

use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    /**
     * Max retry attempts before giving up.
     */
    protected int $maxAttempts = 5;

    /**
     * Base delay in seconds for exponential backoff.
     */
    protected int $baseDelay = 30;

    /**
     * HTTP timeout in seconds.
     */
    protected int $timeout = 10;

    /**
     * Send a webhook for a given event with payload.
     * Creates a delivery record and fires the HTTP request.
     */
    public function send(Webhook $webhook, string $event, array $payload): WebhookDelivery
    {
        $delivery = WebhookDelivery::create([
            'webhook_id' => $webhook->id,
            'event' => $event,
            'payload' => $payload,
            'attempt' => 1,
        ]);

        return $this->attemptDelivery($delivery);
    }

    /**
     * Attempt (or re-attempt) delivery of a webhook payload.
     */
    public function attemptDelivery(WebhookDelivery $delivery): WebhookDelivery
    {
        $webhook = $delivery->webhook;

        if (!$webhook || !$webhook->is_active) {
            $delivery->update([
                'failed_at' => now(),
                'error_message' => 'Webhook is inactive or deleted',
            ]);
            return $delivery;
        }

        $payload = $delivery->payload;
        $signature = $this->signPayload($payload, $webhook->secret);

        $headers = array_merge(
            $webhook->headers ?? [],
            [
                'Content-Type' => 'application/json',
                'X-Webhook-Signature' => $signature,
                'X-Webhook-Event' => $delivery->event,
                'X-Webhook-Delivery-Id' => (string) $delivery->id,
            ]
        );

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders($headers)
                ->post($webhook->url, $payload);

            $delivery->update([
                'response_code' => $response->status(),
                'response_body' => $response->json() ?? $response->body(),
                'delivered_at' => now(),
            ]);

            // Log non-2xx responses as warnings
            if ($response->failed()) {
                Log::warning("Webhook delivery #{$delivery->id} returned {$response->status()}", [
                    'webhook_id' => $webhook->id,
                    'url' => $webhook->url,
                    'event' => $delivery->event,
                ]);
            }

            return $delivery;
        } catch (\Exception $e) {
            $nextAttempt = $delivery->attempt + 1;
            $maxedOut = $nextAttempt > $this->maxAttempts;

            $updateData = [
                'error_message' => $e->getMessage(),
                'response_code' => null,
                'response_body' => null,
            ];

            if ($maxedOut) {
                $updateData['failed_at'] = now();
                $updateData['next_retry_at'] = null;
            } else {
                $updateData['next_retry_at'] = now()->addSeconds(
                    $this->backoffDelay($delivery->attempt)
                );
            }

            $delivery->update($updateData);

            Log::warning("Webhook delivery #{$delivery->id} failed: {$e->getMessage()}", [
                'webhook_id' => $webhook->id,
                'url' => $webhook->url,
                'event' => $delivery->event,
                'attempt' => $delivery->attempt,
                'maxed_out' => $maxedOut,
            ]);

            return $delivery;
        }
    }

    /**
     * Retry a failed delivery. Increments the attempt counter.
     */
    public function retry(WebhookDelivery $delivery): WebhookDelivery
    {
        if ($delivery->delivered_at) {
            throw new \RuntimeException("Delivery #{$delivery->id} was already delivered.");
        }

        $delivery->increment('attempt');

        return $this->attemptDelivery($delivery);
    }

    /**
     * Dispatch all webhooks that subscribe to a given event.
     * Returns array of created delivery records.
     *
     * @return WebhookDelivery[]
     */
    public function dispatch(string $event, array $payload): array
    {
        $webhooks = Webhook::where('is_active', true)
            ->whereJsonContains('events', $event)
            ->get();

        $deliveries = [];

        foreach ($webhooks as $webhook) {
            try {
                $deliveries[] = $this->send($webhook, $event, $payload);
            } catch (\Exception $e) {
                Log::error("Failed to initiate webhook #{$webhook->id} for event '{$event}': {$e->getMessage()}");
            }
        }

        return $deliveries;
    }

    /**
     * Check if a webhook subscribes to a given event.
     */
    public function shouldFire(Webhook $webhook, string $event): bool
    {
        return $webhook->is_active && in_array($event, $webhook->events ?? []);
    }

    /**
     * Generate HMAC-SHA256 signature for webhook payload.
     */
    public function signPayload(array $payload, string $secret): string
    {
        return hash_hmac('sha256', json_encode($payload), $secret);
    }

    /**
     * Calculate exponential backoff delay in seconds.
     * Base delay * 2^(attempt-1), capped at 3600s (1 hour).
     */
    public function backoffDelay(int $attempt): int
    {
        return min($this->baseDelay * (2 ** ($attempt - 1)), 3600);
    }

    /**
     * Get pending retries that are due for re-attempt.
     */
    public function getPendingRetries(): iterable
    {
        return WebhookDelivery::whereNull('delivered_at')
            ->whereNull('failed_at')
            ->where('next_retry_at', '<=', now())
            ->whereHas('webhook', fn($q) => $q->where('is_active', true))
            ->cursor();
    }

    /**
     * Process all due retries. Called by scheduler.
     */
    public function processRetries(): int
    {
        $processed = 0;

        foreach ($this->getPendingRetries() as $delivery) {
            try {
                $this->retry($delivery);
                $processed++;
            } catch (\Exception $e) {
                Log::error("Retry failed for delivery #{$delivery->id}: {$e->getMessage()}");
            }
        }

        return $processed;
    }
}
