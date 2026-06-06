<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Inbound;
use App\Models\InboundItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InboundController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Inbound::with('warehouse', 'user');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        $inbounds = $query->orderByDesc('created_at')->paginate($request->get('per_page', 25));
        return response()->json($inbounds);
    }

    public function show(string|int $inbound): JsonResponse
    {
        $inbound = Inbound::with(['warehouse', 'user', 'items.product'])->findOrFail($inbound);
        return response()->json(['data' => $inbound]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'source_type' => 'nullable|string',
            'source_reference' => 'nullable|string',
            'expected_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'nullable|array',
        ]);

        $inbound = Inbound::create([
            'inbound_number' => 'INB-' . date('Ymd') . '-' . str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT),
            'warehouse_id' => $validated['warehouse_id'],
            'user_id' => $request->user()->id,
            'status' => 'pending',
            'expected_date' => $validated['expected_date'] ?? null,
            'source_type' => $validated['source_type'] ?? null,
            'source_reference' => $validated['source_reference'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        if (!empty($validated['items'])) {
            foreach ($validated['items'] as $item) {
                $inbound->items()->create($item);
            }
        }

        return response()->json(['data' => $inbound->load('items')], 201);
    }

    public function update(Request $request, string|int $inbound): JsonResponse
    {
        $inbound = Inbound::findOrFail($inbound);
        $inbound->update($request->only([
            'source_type', 'source_reference', 'expected_date', 'notes', 'status',
        ]));
        return response()->json(['data' => $inbound]);
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

    public function receive(Request $request, string|int $inbound): JsonResponse
    {
        $inbound = Inbound::findOrFail($inbound);
        if ($inbound->status !== 'pending') {
            return response()->json(['message' => 'Inbound already received'], 422);
        }

        $inbound->update([
            'status' => 'received',
            'received_date' => now(),
        ]);

        // Create stock items for each inbound item
        foreach ($inbound->items as $item) {
            $item->update(['received_qty' => $item->qty, 'received_at' => now()]);
        }

        return response()->json(['data' => $inbound]);
    }

    public function cancel(Request $request, string|int $inbound): JsonResponse
    {
        $inbound = Inbound::findOrFail($inbound);
        if (in_array($inbound->status, ['received', 'cancelled'])) {
            return response()->json(['message' => 'Cannot cancel this inbound'], 422);
        }
        $inbound->update(['status' => 'cancelled']);
        return response()->json(['data' => $inbound]);
    }
}