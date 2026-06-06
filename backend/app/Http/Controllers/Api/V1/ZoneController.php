<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\WarehouseZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ZoneController extends Controller
{
    public function index(string|int $warehouse): JsonResponse
    {
        $zones = WarehouseZone::where('warehouse_id', $warehouse)
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
            'color' => 'nullable|string|max:7',
            'min_temp' => 'nullable|numeric',
            'max_temp' => 'nullable|numeric',
            'min_humidity' => 'nullable|integer',
            'max_humidity' => 'nullable|integer',
            'allowed_categories' => 'nullable|array',
            'sort_order' => 'nullable|integer',
        ]);

        $zone = WarehouseZone::create([
            'warehouse_id' => $warehouse,
            'code' => $request->code,
            'name' => $request->name,
            'color' => $request->color ?? '#3B82F6',
            'min_temp' => $request->min_temp,
            'max_temp' => $request->max_temp,
            'min_humidity' => $request->min_humidity,
            'max_humidity' => $request->max_humidity,
            'allowed_categories' => $request->allowed_categories,
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => true,
        ]);

        return response()->json(['data' => $zone], 201);
    }

    public function show(string|int $warehouse, string|int $zone): JsonResponse
    {
        $zone = WarehouseZone::where('warehouse_id', $warehouse)
            ->where('id', $zone)
            ->with('racks')
            ->firstOrFail();
        return response()->json(['data' => $zone]);
    }

    public function update(Request $request, string|int $warehouse, string|int $zone): JsonResponse
    {
        $zone = WarehouseZone::where('warehouse_id', $warehouse)->where('id', $zone)->firstOrFail();

        $request->validate([
            'code' => 'sometimes|required|string|max:10',
            'name' => 'sometimes|required|string|max:100',
            'color' => 'nullable|string|max:7',
            'min_temp' => 'nullable|numeric',
            'max_temp' => 'nullable|numeric',
            'min_humidity' => 'nullable|integer',
            'max_humidity' => 'nullable|integer',
            'allowed_categories' => 'nullable|array',
            'sort_order' => 'nullable|integer',
        ]);

        $zone->update($request->only([
            'code', 'name', 'color', 'min_temp', 'max_temp',
            'min_humidity', 'max_humidity', 'allowed_categories', 'sort_order',
        ]));

        return response()->json(['data' => $zone]);
    }

    public function destroy(string|int $warehouse, string|int $zone): JsonResponse
    {
        $zone = WarehouseZone::where('warehouse_id', $warehouse)->where('id', $zone)->firstOrFail();
        $zone->delete();
        return response()->json(['message' => 'Zone deleted']);
    }

    public function activate(string|int $warehouse, string|int $zone): JsonResponse
    {
        $zone = WarehouseZone::where('warehouse_id', $warehouse)->where('id', $zone)->firstOrFail();
        $zone->update(['is_active' => true]);
        return response()->json(['data' => $zone]);
    }

    public function deactivate(string|int $warehouse, string|int $zone): JsonResponse
    {
        $zone = WarehouseZone::where('warehouse_id', $warehouse)->where('id', $zone)->firstOrFail();
        $zone->update(['is_active' => false]);
        return response()->json(['data' => $zone]);
    }
}