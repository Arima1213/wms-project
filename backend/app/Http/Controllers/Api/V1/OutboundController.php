<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Outbound;
use App\Http\Requests\StoreOutboundRequest;
use App\Http\Requests\UpdateOutboundRequest;
use App\Services\OutboundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OutboundController extends Controller
{
    protected OutboundService $outboundService;

    public function __construct(OutboundService $outboundService)
    {
        $this->outboundService = $outboundService;
    }

    public function index(Request $request): JsonResponse
    {
        $query = Outbound::with('warehouse', 'user');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        $outbounds = $query->orderByDesc('created_at')->paginate($request->get('per_page', 25));
        return response()->json($outbounds);
    }

    public function show(string|int $outbound): JsonResponse
    {
        $outbound = Outbound::with(['warehouse', 'user', 'items.product'])->findOrFail($outbound);
        return response()->json(['data' => $outbound]);
    }

    public function store(StoreOutboundRequest $request): JsonResponse
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $outbound = Outbound::create([
                'outbound_number' => 'OUT-' . date('Ymd') . '-' . str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT),
                'warehouse_id' => $validated['warehouse_id'],
                'user_id' => $request->user()->id,
                'status' => 'pending',
                'customer_name' => $validated['customer_name'] ?? null,
                'shipping_address' => $validated['shipping_address'] ?? null,
                'destination_type' => $validated['destination_type'] ?? null,
                'destination_reference' => $validated['destination_reference'] ?? null,
                'expected_date' => $validated['expected_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            if (!empty($validated['items'])) {
                foreach ($validated['items'] as $item) {
                    $outbound->items()->create($item);
                }
            }

            DB::commit();
            return response()->json(['data' => $outbound->load('items')], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create outbound', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(UpdateOutboundRequest $request, string|int $outbound): JsonResponse
    {
        $outbound = Outbound::findOrFail($outbound);
        $outbound->update($request->validated());
        return response()->json(['data' => $outbound]);
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
        if ($outbound->status !== 'pending') {
            return response()->json(['message' => 'Outbound already picked'], 422);
        }
        $outbound->update(['status' => 'picking']);
        return response()->json(['data' => $outbound]);
    }

    public function ship(Request $request, string|int $outboundId): JsonResponse
    {
        $outbound = Outbound::findOrFail($outboundId);
        
        try {
            $shippedOutbound = $this->outboundService->ship($outbound, $request->user()->id);
            return response()->json(['data' => $shippedOutbound]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function cancel(Request $request, string|int $outbound): JsonResponse
    {
        $outbound = Outbound::findOrFail($outbound);
        if (in_array($outbound->status, ['shipped', 'cancelled'])) {
            return response()->json(['message' => 'Cannot cancel this outbound'], 422);
        }
        $outbound->update(['status' => 'cancelled']);
        return response()->json(['data' => $outbound]);
    }
}