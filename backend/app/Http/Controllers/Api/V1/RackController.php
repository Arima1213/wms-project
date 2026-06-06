<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Rack;
use App\Models\RackLevel;
use App\Models\WarehouseZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RackController extends Controller
{
    public function index(string|int $warehouse, string|int $zone): JsonResponse
    {
        $zone = WarehouseZone::where('id', $zone)->where('warehouse_id', $warehouse)->firstOrFail();
        $racks = Rack::where('zone_id', $zone->id)
            ->withCount('levels', 'slots')
            ->orderBy('code')
            ->paginate(50);
        return response()->json($racks);
    }

    public function store(Request $request, string|int $warehouse, string|int $zone): JsonResponse
    {
        $zone = WarehouseZone::where('id', $zone)->where('warehouse_id', $warehouse)->firstOrFail();

        $request->validate([
            'code' => 'required|string|max:20',
            'name' => 'nullable|string|max:100',
            'pos_x' => 'nullable|numeric',
            'pos_y' => 'nullable|numeric',
            'width' => 'nullable|numeric',
            'depth' => 'nullable|numeric',
            'levels' => 'nullable|integer|min:1|max:10',
            'columns_per_level' => 'nullable|integer|min:1|max:10',
            'max_weight_per_kg' => 'nullable|numeric',
        ]);

        $rack = Rack::create([
            'zone_id' => $zone->id,
            'code' => $request->code,
            'name' => $request->name,
            'pos_x' => $request->pos_x ?? 0,
            'pos_y' => $request->pos_y ?? 0,
            'width' => $request->width ?? 4,
            'depth' => $request->depth ?? 2,
            'levels' => $request->levels ?? 3,
            'columns_per_level' => $request->columns_per_level ?? 4,
            'max_weight_kg' => $request->max_weight_per_kg ?? 500,
            'is_active' => true,
        ]);

        return response()->json(['data' => $rack], 201);
    }

    public function show(string|int $warehouse, string|int $zone, string|int $rack): JsonResponse
    {
        $rack = Rack::whereHas('zone', fn($q) => $q->where('warehouse_id', $warehouse)->where('id', $zone))
            ->where('id', $rack)
            ->with('levels.slots')
            ->firstOrFail();
        return response()->json(['data' => $rack]);
    }

    public function update(Request $request, string|int $warehouse, string|int $zone, string|int $rack): JsonResponse
    {
        $rack = Rack::whereHas('zone', fn($q) => $q->where('warehouse_id', $warehouse)->where('id', $zone))
            ->where('id', $rack)
            ->firstOrFail();

        $request->validate([
            'code' => 'sometimes|required|string|max:20',
            'name' => 'nullable|string|max:100',
            'pos_x' => 'nullable|numeric',
            'pos_y' => 'nullable|numeric',
            'width' => 'nullable|numeric',
            'depth' => 'nullable|numeric',
            'levels' => 'nullable|integer|min:1|max:10',
            'columns_per_level' => 'nullable|integer|min:1|max:10',
            'max_weight_per_kg' => 'nullable|numeric',
        ]);

        $rack->update($request->only([
            'code', 'name', 'pos_x', 'pos_y', 'width', 'depth',
            'levels', 'columns_per_level',
        ]));
        if ($request->has('max_weight_per_kg')) {
            $rack->max_weight_kg = $request->max_weight_per_kg;
            $rack->save();
        }

        return response()->json(['data' => $rack]);
    }

    public function destroy(string|int $warehouse, string|int $zone, string|int $rack): JsonResponse
    {
        $rack = Rack::whereHas('zone', fn($q) => $q->where('warehouse_id', $warehouse)->where('id', $zone))
            ->where('id', $rack)
            ->firstOrFail();
        $rack->delete();
        return response()->json(['message' => 'Rack deleted']);
    }

    public function slots(string|int $rack): JsonResponse
    {
        $rack = Rack::with('levels.slots')->findOrFail($rack);
        $slots = collect();
        foreach ($rack->levels as $level) {
            foreach ($level->slots as $slot) {
                $slots->push($slot);
            }
        }
        return response()->json(['data' => $slots]);
    }

    public function updatePosition(Request $request, string|int $rack): JsonResponse
    {
        $request->validate([
            'pos_x' => 'required|numeric',
            'pos_y' => 'required|numeric',
        ]);

        $rack = Rack::findOrFail($rack);
        $rack->update([
            'pos_x' => $request->pos_x,
            'pos_y' => $request->pos_y,
        ]);

        return response()->json(['data' => $rack]);
    }
}