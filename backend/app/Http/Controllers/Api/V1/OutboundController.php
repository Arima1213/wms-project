<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Outbound;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OutboundController extends Controller
{
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

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'destination_type' => 'nullable|string',
            'destination_reference' => 'nullable|string',
            'customer_name' => 'nullable|string',
            'shipping_address' => 'nullable|string',
            'expected_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'nullable|array',
        ]);

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

        return response()->json(['data' => $outbound->load('items')], 201);
    }

    public function update(Request $request, string|int $outbound): JsonResponse
    {
        $outbound = Outbound::findOrFail($outbound);
        $outbound->update($request->only([
            'destination_type', 'destination_reference', 'customer_name',
            'shipping_address', 'expected_date', 'notes', 'status',
        ]));
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

    public function ship(Request $request, string|int $outbound): JsonResponse
    {
        $outbound = Outbound::findOrFail($outbound);
        if (!in_array($outbound->status, ['pending', 'picking'])) {
            return response()->json(['message' => 'Cannot ship this outbound'], 422);
        }
        $outbound->update([
            'status' => 'shipped',
            'shipped_date' => now(),
        ]);

        foreach ($outbound->items as $item) {
            $item->update(['picked_qty' => $item->qty]);

            // Deduct from Inventory
            $inventory = \App\Models\Inventory::where([
                'product_id' => $item->product_id,
                'warehouse_id' => $outbound->warehouse_id,
            ])->first();
            
            if ($inventory) {
                $inventory->quantity = max(0, $inventory->quantity - $item->qty);
                $inventory->save();

                // Record transaction
                \App\Models\InventoryTransaction::create([
                    'inventory_id' => $inventory->id,
                    'type' => 'out',
                    'quantity' => $item->qty,
                    'reference_type' => 'outbound',
                    'reference_id' => $outbound->id,
                    'notes' => 'Shipped for outbound ' . $outbound->outbound_number,
                    'created_by' => $request->user()->id,
                ]);
            }
        }

        return response()->json(['data' => $outbound]);
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