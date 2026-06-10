<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Outbound;
use App\Http\Requests\StoreOutboundRequest;
use App\Http\Requests\UpdateOutboundRequest;
use App\Http\Resources\OutboundResource;
use App\Services\DocumentSequenceService;
use App\Services\OutboundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OutboundController extends Controller
{
    protected OutboundService $outboundService;
    protected DocumentSequenceService $documentSequence;

    public function __construct(OutboundService $outboundService, DocumentSequenceService $documentSequence)
    {
        $this->outboundService = $outboundService;
        $this->documentSequence = $documentSequence;
    }

    public function index(Request $request)
    {
        $query = Outbound::with(['warehouse', 'user', 'items.product']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        $outbounds = $query->orderByDesc('created_at')->paginate($request->get('per_page', 25));
        return OutboundResource::collection($outbounds);
    }

    public function show(string|int $outbound): OutboundResource
    {
        $outbound = Outbound::with(['warehouse', 'user', 'items.product'])->findOrFail($outbound);
        return new OutboundResource($outbound);
    }

    public function store(StoreOutboundRequest $request): JsonResponse
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $outbound = Outbound::create([
                'outbound_number' => $this->documentSequence->getNextNumber('OUT'),
                'warehouse_id' => $validated['warehouse_id'],
                'created_by' => $request->user()->id,
                'status' => 'pending',
                'type' => $validated['type'] ?? $validated['destination_type'] ?? 'sales',
                'destination_name' => $validated['customer_name'] ?? $validated['destination_name'] ?? null,
                'destination_address' => $validated['shipping_address'] ?? $validated['destination_address'] ?? null,
                'reference_number' => $validated['destination_reference'] ?? $validated['reference_number'] ?? null,
                'order_date' => $validated['expected_date'] ?? $validated['order_date'] ?? now()->toDateString(),
                'notes' => $validated['notes'] ?? null,
            ]);

            if (!empty($validated['items'])) {
                foreach ($validated['items'] as $item) {
                    $outbound->items()->create([
                        'product_id' => $item['product_id'],
                        'ordered_qty' => $item['qty'] ?? $item['ordered_qty'] ?? 0,
                        'batch_number' => $item['batch_number'] ?? null,
                        'expiry_date' => $item['expiry_date'] ?? null,
                        'notes' => $item['notes'] ?? null,
                    ]);
                }
            }

            DB::commit();
            return response()->json(['data' => new OutboundResource($outbound->load('items.product', 'warehouse', 'user'))], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create outbound', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(UpdateOutboundRequest $request, string|int $outbound): JsonResponse
    {
        $outbound = Outbound::findOrFail($outbound);
        $outbound->update($request->validated());
        return response()->json(['data' => new OutboundResource($outbound->fresh(['warehouse', 'user']))]);
    }

    public function destroy(string|int $outbound): JsonResponse
    {
        $outbound = Outbound::findOrFail($outbound);
        if ($outbound->status !== 'pending') {
            return response()->json(['message' => 'Only pending outbounds can be deleted'], 422);
        }
        $outbound->delete();
        return response()->json(['message' => 'Outbound deleted']);
    }

    public function pick(Request $request, string|int $outbound): JsonResponse
    {
        $outbound = Outbound::findOrFail($outbound);
        $this->authorize('pick', $outbound);

        if ($outbound->status !== 'pending') {
            return response()->json(['message' => 'Outbound already picked'], 422);
        }
        $outbound->update(['status' => 'picking']);
        return response()->json(['data' => new OutboundResource($outbound->fresh(['warehouse', 'user']))]);
    }

    public function ship(Request $request, string|int $outboundId): JsonResponse
    {
        $outbound = Outbound::findOrFail($outboundId);
        $this->authorize('ship', $outbound);
        
        try {
            $shippedOutbound = $this->outboundService->ship($outbound, $request->user()->id);
            return response()->json(['data' => new OutboundResource($shippedOutbound->load(['warehouse', 'user', 'items.product']))]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function cancel(Request $request, string|int $outbound): JsonResponse
    {
        $outbound = Outbound::findOrFail($outbound);
        $this->authorize('cancel', $outbound);

        if (in_array($outbound->status, ['shipped', 'cancelled'])) {
            return response()->json(['message' => 'Cannot cancel this outbound'], 422);
        }
        $outbound->update(['status' => 'cancelled']);
        return response()->json(['data' => new OutboundResource($outbound->load(['warehouse', 'user']))]);
    }
}
