<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Zone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ZoneController extends Controller
{
    public function index(string|int $warehouse): JsonResponse
    {
        $zones = Zone::where('warehouse_id', $warehouse)
            ->withCount('racks')
            ->orderBy('sort_order')
            ->paginate(50);
        return response()->json($zones);
    }

    public function store(Request $request, string|int $warehouse): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:10',
            'name' => 'required|string|max:100',
            'zone_type' => 'nullable|in:fast_moving,slow_moving,heavy,cold,hazmat',
            'color' => 'nullable|string|max:7',
            'temperature_range' => 'nullable|array',
            'humidity_range' => 'nullable|array',
            'allowed_product_types' => 'nullable|array',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        $zone = Zone::create([
            'warehouse_id' => $warehouse,
            'code' => $request->code,
            'name' => $request->name,
            'zone_type' => $request->zone_type ?? 'fast_moving',
            'color' => $request->color ?? '#3B82F6',
            'temperature_range' => $request->temperature_range,
            'humidity_range' => $request->humidity_range,
            'allowed_product_types' => $request->allowed_product_types,
            'description' => $request->description,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => true,
        ]);

        return response()->json(['data' => $zone], 201);
    }

    public function show(string|int $warehouse, string|int $zone): JsonResponse
    {
        $zone = Zone::where('warehouse_id', $warehouse)
            ->where('id', $zone)
            ->with('racks')
            ->firstOrFail();
        return response()->json(['data' => $zone]);
    }

    public function update(Request $request, string|int $warehouse, string|int $zone): JsonResponse
    {
        $zone = Zone::where('warehouse_id', $warehouse)->where('id', $zone)->firstOrFail();

        $request->validate([
            'code' => 'sometimes|required|string|max:10',
            'name' => 'sometimes|required|string|max:100',
            'zone_type' => 'nullable|in:fast_moving,slow_moving,heavy,cold,hazmat',
            'color' => 'nullable|string|max:7',
            'temperature_range' => 'nullable|array',
            'humidity_range' => 'nullable|array',
            'allowed_product_types' => 'nullable|array',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        $zone->update($request->only([
            'code', 'name', 'zone_type', 'color',
            'temperature_range', 'humidity_range', 'allowed_product_types',
            'description', 'sort_order',
        ]));

        return response()->json(['data' => $zone]);
    }

    public function destroy(string|int $warehouse, string|int $zone): JsonResponse
    {
        $zone = Zone::where('warehouse_id', $warehouse)->where('id', $zone)->firstOrFail();
        $zone->delete();
        return response()->json(['message' => 'Zone deleted']);
    }

    public function activate(string|int $zone): JsonResponse
    {
        $zone = Zone::findOrFail($zone);
        $zone->update(['is_active' => true]);
        return response()->json(['data' => $zone]);
    }

    public function deactivate(string|int $zone): JsonResponse
    {
        $zone = Zone::findOrFail($zone);
        $zone->update(['is_active' => false]);
        return response()->json(['data' => $zone]);
    }
}