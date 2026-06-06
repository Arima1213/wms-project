<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\RackSlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RackSlotController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = RackSlot::with('rackLevel.rack.zone.warehouse');

        if ($request->has('rack_id')) {
            $query->whereHas('rackLevel', fn($q) => $q->where('rack_id', $request->rack_id));
        }
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $slots = $query->paginate($request->get('per_page', 50));
        return response()->json($slots);
    }

    public function show(string|int $slot): JsonResponse
    {
        $slot = RackSlot::with('rackLevel.rack.zone')->findOrFail($slot);
        return response()->json(['data' => $slot]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'rack_level_id' => 'required|exists:rack_levels,id',
            'slot_number' => 'required|integer',
            'slot_code' => 'required|string|max:30|unique:rack_slots,slot_code',
            'max_weight_kg' => 'nullable|numeric',
            'max_height_cm' => 'nullable|numeric',
            'max_width_cm' => 'nullable|numeric',
            'max_depth_cm' => 'nullable|numeric',
            'slot_type' => 'nullable|in:fixed,floating',
        ]);

        $slot = RackSlot::create($request->only([
            'rack_level_id', 'slot_number', 'slot_code',
            'max_weight_kg', 'max_height_cm', 'max_width_cm', 'max_depth_cm', 'slot_type',
        ]) + ['status' => 'empty', 'is_active' => true]);

        return response()->json(['data' => $slot], 201);
    }

    public function update(Request $request, string|int $slot): JsonResponse
    {
        $slot = RackSlot::findOrFail($slot);

        $request->validate([
            'slot_code' => 'sometimes|required|string|max:30|unique:rack_slots,slot_code,' . $slot->id,
            'max_weight_kg' => 'nullable|numeric',
            'slot_type' => 'nullable|in:fixed,floating',
            'is_active' => 'nullable|boolean',
        ]);

        $slot->update($request->only([
            'slot_code', 'max_weight_kg', 'slot_type', 'is_active',
        ]));

        return response()->json(['data' => $slot]);
    }

    public function destroy(string|int $slot): JsonResponse
    {
        $slot = RackSlot::findOrFail($slot);
        $slot->delete();
        return response()->json(['message' => 'Slot deleted']);
    }

    public function assignProduct(Request $request, string|int $slot): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'nullable|integer|min:1',
        ]);

        $slot = RackSlot::findOrFail($slot);
        $slot->update([
            'fixed_product_id' => $request->product_id,
            'status' => 'partial',
        ]);

        return response()->json(['data' => $slot]);
    }

    public function unassignProduct(string|int $slot): JsonResponse
    {
        $slot = RackSlot::findOrFail($slot);
        $slot->update([
            'fixed_product_id' => null,
            'status' => 'empty',
        ]);

        return response()->json(['data' => $slot]);
    }

    public function reserve(Request $request, string|int $slot): JsonResponse
    {
        $request->validate([
            'reserved_for' => 'required|string|max:100',
            'reserved_until' => 'required|date',
        ]);

        $slot = RackSlot::findOrFail($slot);
        $slot->update([
            'status' => 'reserved',
            'reserved_for' => $request->reserved_for,
            'reserved_until' => $request->reserved_until,
        ]);

        return response()->json(['data' => $slot]);
    }
}