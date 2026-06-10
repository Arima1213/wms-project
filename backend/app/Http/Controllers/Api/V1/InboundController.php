<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Inbound;
use App\Http\Requests\StoreInboundRequest;
use App\Http\Requests\UpdateInboundRequest;
use App\Http\Resources\InboundResource;
use App\Services\DocumentSequenceService;
use App\Services\InboundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InboundController extends Controller
{
    protected InboundService $inboundService;
    protected DocumentSequenceService $documentSequence;

    public function __construct(InboundService $inboundService, DocumentSequenceService $documentSequence)
    {
        $this->inboundService = $inboundService;
        $this->documentSequence = $documentSequence;
    }

    public function index(Request $request)
    {
        $query = Inbound::with(['warehouse', 'user', 'items.product', 'supplier']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        $inbounds = $query->orderByDesc('created_at')->paginate($request->get('per_page', 25));
        return InboundResource::collection($inbounds);
    }

    public function show(string|int $inbound): InboundResource
    {
        $inbound = Inbound::with(['warehouse', 'user', 'items.product', 'supplier'])->findOrFail($inbound);
        return new InboundResource($inbound);
    }

    public function store(StoreInboundRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Map frontend source_type values to DB enum values
        $sourceTypeMap = [
            'purchase_order' => 'purchase',
            'return' => 'return',
            'transfer_in' => 'transfer',
            'other' => 'other',
        ];
        $sourceType = $sourceTypeMap[$validated['source_type'] ?? 'other'] ?? 'other';

        DB::beginTransaction();
        try {
            $inbound = Inbound::create([
                'inbound_number' => $this->documentSequence->getNextNumber('INB'),
                'warehouse_id' => $validated['warehouse_id'],
                'created_by' => $request->user()->id,
                'status' => 'pending',
                'expected_date' => $validated['expected_date'] ?? null,
                'source_type' => $sourceType,
                'source_reference' => $validated['source_reference'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            if (!empty($validated['items'])) {
                foreach ($validated['items'] as $item) {
                    $inbound->items()->create([
                        'product_id' => $item['product_id'],
                        'expected_qty' => $item['qty'] ?? $item['expected_qty'] ?? 0,
                        'batch_number' => $item['batch_number'] ?? null,
                        'expiry_date' => $item['expiry_date'] ?? null,
                        'notes' => $item['notes'] ?? null,
                    ]);
                }
            }

            DB::commit();
            return response()->json(['data' => new InboundResource($inbound->load('items.product', 'warehouse', 'user'))], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create inbound', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(UpdateInboundRequest $request, string|int $inbound): JsonResponse
    {
        $inbound = Inbound::findOrFail($inbound);
        $inbound->update($request->validated());
        return response()->json(['data' => new InboundResource($inbound->fresh(['warehouse', 'user']))]);
    }

    public function destroy(string|int $inbound): JsonResponse
    {
        $inbound = Inbound::findOrFail($inbound);
        if ($inbound->status !== 'pending') {
            return response()->json(['message' => 'Only pending inbounds can be deleted'], 422);
        }
        $inbound->delete();
        return response()->json(['message' => 'Inbound deleted']);
    }

    public function receive(Request $request, string|int $inboundId): JsonResponse
    {
        $inbound = Inbound::findOrFail($inboundId);
        $this->authorize('receive', $inbound);

        $validated = $request->validate([
            'items' => 'nullable|array',
            'items.*.id' => 'required_with:items|integer|exists:inbound_items,id',
            'items.*.received_qty' => 'required_with:items|numeric|min:0',
        ]);

        $items = $validated['items'] ?? null;

        try {
            $receivedInbound = $this->inboundService->receive($inbound, $request->user()->id, $items);
            return response()->json(['data' => new InboundResource($receivedInbound->load(['warehouse', 'user', 'items.product', 'supplier']))]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function cancel(Request $request, string|int $inbound): JsonResponse
    {
        $inbound = Inbound::findOrFail($inbound);
        $this->authorize('cancel', $inbound);

        if (in_array($inbound->status, ['received', 'cancelled'])) {
            return response()->json(['message' => 'Cannot cancel this inbound'], 422);
        }
        $inbound->update(['status' => 'cancelled']);
        return response()->json(['data' => new InboundResource($inbound->load(['warehouse', 'user']))]);
    }
}
