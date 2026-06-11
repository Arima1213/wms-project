<?php

namespace App\Jobs;

use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeliverWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum number of retry attempts.
     */
    public int $tries = 3;

    /**
     * Backoff delays in seconds between retry attempts.
     */
    public array $backoff = [30, 120];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Webhook $webhook,
        public WebhookDelivery $delivery,
        public string $event,
        public array $payload
    ) {}

    /**
     * Execute the job.
     *
     * Dispatches the webhook HTTP POST, records the result on the delivery,
     * and marks delivered_at on success. On failure, the exception triggers
     * Laravel's built-in retry (based on $tries / $backoff). After all
     * attempts exhausted, failed() is called.
     */
    public function handle(): void
    {
        $signature = hash_hmac('sha256', json_encode($this->payload), $this->webhook->secret);

        $headers = array_merge(
            $this->webhook->headers ?? [],
            [
                'Content-Type' => 'application/json',
                'X-Webhook-Signature' => $signature,
                'X-Webhook-Event' => $this->event,
                'X-Webhook-Delivery-Id' => (string) $this->delivery->id,
            ]
        );

        $response = Http::timeout(15)
            ->withHeaders($headers)
            ->post($this->webhook->url, $this->payload);

        // Record HTTP response
        $this->delivery->update([
            'response_code' => $response->status(),
            'response_body' => $response->json() ?? $response->body(),
        ]);

        // Throw on failure so Laravel automatically retries (up to $tries)
        if ($response->failed()) {
            Log::warning("Webhook delivery #{$this->delivery->id} returned HTTP {$response->status()}", [
                'webhook_id' => $this->webhook->id,
                'url' => $this->webhook->url,
                'event' => $this->event,
            ]);

            throw new \RuntimeException(
                "Webhook returned HTTP {$response->status()}: {$response->body()}"
            );
        }

        // Mark as successfully delivered
        $this->delivery->update([
            'delivered_at' => now(),
        ]);
    }

    /**
     * Handle a final failure after all retry attempts are exhausted.
     */
    public function failed(\Throwable $exception): void
    {
        $this->delivery->update([
            'failed_at' => now(),
            'error_message' => $exception->getMessage(),
        ]);

        Log::error("Webhook delivery #{$this->delivery->id} permanently failed after {$this->attempts()} attempt(s): {$exception->getMessage()}", [
            'webhook_id' => $this->webhook->id,
            'url' => $this->webhook->url,
            'event' => $this->event,
        ]);
    }
}
