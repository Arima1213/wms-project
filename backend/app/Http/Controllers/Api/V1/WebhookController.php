<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWebhookRequest;
use App\Http\Requests\UpdateWebhookRequest;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use App\Services\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    protected WebhookService $webhookService;

    public function __construct(WebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    /**
     * List all webhooks.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Webhook::with('creator:id,name');

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }
        if ($request->has('event')) {
            $query->whereJsonContains('events', $request->event);
        }

        $webhooks = $query->orderByDesc('created_at')->paginate($request->get('per_page', 25));

        return response()->json($webhooks);
    }

    /**
     * Show a single webhook with recent deliveries.
     */
    public function show(string|int $id): JsonResponse
    {
        $webhook = Webhook::with('creator:id,name')->findOrFail($id);

        $webhook->load(['deliveries' => fn($q) => $q->latest()->limit(10)]);

        return response()->json(['data' => $webhook]);
    }

    /**
     * Create a new webhook.
     */
    public function store(StoreWebhookRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $webhook = Webhook::create([
            'name' => $validated['name'],
            'url' => $validated['url'],
            'secret' => $validated['secret'],
            'events' => $validated['events'],
            'is_active' => $validated['is_active'] ?? true,
            'headers' => $validated['headers'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['data' => $webhook], 201);
    }

    /**
     * Update an existing webhook.
     */
    public function update(UpdateWebhookRequest $request, string|int $id): JsonResponse
    {
        $webhook = Webhook::findOrFail($id);
        $validated = $request->validated();

        $fillable = array_intersect_key($validated, array_flip([
            'name', 'url', 'events', 'is_active', 'headers',
        ]));

        if ($request->has('secret') && $request->secret !== null) {
            $fillable['secret'] = $request->secret;
        }

        $webhook->update($fillable);

        return response()->json(['data' => $webhook]);
    }

    /**
     * Delete a webhook.
     */
    public function destroy(string|int $id): JsonResponse
    {
        $webhook = Webhook::findOrFail($id);
        $webhook->delete();

        return response()->json(null, 204);
    }

    /**
     * Test a webhook by sending a ping event.
     */
    public function test(string|int $id): JsonResponse
    {
        $webhook = Webhook::findOrFail($id);

        $delivery = $this->webhookService->send($webhook, 'ping', [
            'event' => 'ping',
            'timestamp' => now()->toIso8601String(),
            'test' => true,
        ]);

        return response()->json([
            'message' => 'Test webhook sent',
            'data' => $delivery,
        ]);
    }

    /**
     * List deliveries for a webhook.
     */
    public function deliveries(Request $request, string|int $id): JsonResponse
    {
        $webhook = Webhook::findOrFail($id);

        $query = $webhook->deliveries();

        if ($request->has('failed')) {
            $query->whereNotNull('failed_at');
        }
        if ($request->has('event')) {
            $query->where('event', $request->event);
        }

        $deliveries = $query->orderByDesc('created_at')->paginate($request->get('per_page', 25));

        return response()->json($deliveries);
    }

    /**
     * Show a specific delivery detail.
     */
    public function showDelivery(string|int $webhookId, string|int $deliveryId): JsonResponse
    {
        $delivery = WebhookDelivery::where('webhook_id', $webhookId)
            ->findOrFail($deliveryId);

        return response()->json(['data' => $delivery]);
    }

    /**
     * Retry a failed delivery.
     */
    public function retryDelivery(string|int $webhookId, string|int $deliveryId): JsonResponse
    {
        $delivery = WebhookDelivery::where('webhook_id', $webhookId)
            ->findOrFail($deliveryId);

        try {
            $delivery = $this->webhookService->retry($delivery);
            return response()->json(['data' => $delivery]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
