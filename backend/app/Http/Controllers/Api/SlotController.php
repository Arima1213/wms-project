<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RackSlot;
use App\Models\RackLevel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SlotController extends Controller
{
    public function byLevel(RackLevel $level): JsonResponse
    {
        return response()->json($level->slots()->with('currentStock.product')->orderBy('slot_number')->get());
    }

    public function search(Request $request): JsonResponse
    {
        $slots = RackSlot::with(['rackLevel.rack.zone.warehouse', 'currentStock.product'])
            ->where('slot_code', 'ilike', '%' . $request->q . '%')
            ->orWhereHas('currentStock.product', fn($q) => $q->where('name', 'ilike', '%' . $request->q . '%'))
            ->limit(20)
            ->get();

        return response()->json($slots);
    }

    public function assign(Request $request, RackSlot $slot): JsonResponse
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'batch_id' => 'nullable|exists:product_batches,id',
            'quantity' => 'required|numeric|min:0',
            'uom_id' => 'nullable|exists:uoms,id',
            'unit_cost' => 'nullable|numeric',
        ]);

        $slot->currentStock?->update(['is_current' => false]);

        $slot->stockHistory()->create(array_merge($data, ['is_current' => true]));

        return response()->json($slot->fresh('currentStock'));
    }

    public function reserve(Request $request, RackSlot $slot): JsonResponse
    {
        $data = $request->validate([
            'reserved_until' => 'required|date',
            'reserved_for' => 'required|string|max:100',
        ]);

        $slot->update(array_merge($data, ['is_reserved' => true]));
        return response()->json($slot);
    }

    public function unreserve(RackSlot $slot): JsonResponse
    {
        $slot->update(['is_reserved' => false, 'reserved_until' => null, 'reserved_for' => null]);
        return response()->json($slot);
    }
}
