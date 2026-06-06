<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Models\WarehouseZone;
use App\Models\Planogram;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Warehouse::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(fn($q) => $q
                ->where('name', 'ilike', "%{$search}%")
                ->orWhere('code', 'ilike', "%{$search}%")
            );
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $warehouses = $query->orderBy('name')->paginate($request->get('per_page', 25));
        return response()->json($warehouses);
    }

    public function show(string|int $warehouse): JsonResponse
    {
        $warehouse = Warehouse::with([
            'zones' => fn($q) => $q->withCount('racks')->orderBy('sort_order'),
            'zones.racks' => fn($q) => $q->withCount('levels')->orderBy('code'),
            'zones.racks.levels.slots',
            'planogram' => fn($q) => $q->latest()->take(1),
        ])->findOrFail($warehouse);

        return response()->json(['data' => $warehouse]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:20|unique:warehouses,code',
            'name' => 'required|string|max:100',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'capacity_sqm' => 'nullable|numeric',
            'type' => 'nullable|in:reguler,cold_storage,bonded,konsinyasi',
            'pic_name' => 'nullable|string|max:100',
            'pic_phone' => 'nullable|string|max:20',
            'pic_email' => 'nullable|email',
            'operational_hours' => 'nullable|array',
        ]);

        $warehouse = Warehouse::create($request->only([
            'code', 'name', 'address', 'latitude', 'longitude',
            'capacity_sqm', 'type', 'pic_name', 'pic_phone', 'pic_email', 'operational_hours',
        ]) + ['is_active' => true]);

        return response()->json(['data' => $warehouse], 201);
    }

    public function update(Request $request, string|int $warehouse): JsonResponse
    {
        $warehouse = Warehouse::findOrFail($warehouse);

        $request->validate([
            'code' => 'sometimes|required|string|max:20|unique:warehouses,code,' . $warehouse->id,
            'name' => 'sometimes|required|string|max:100',
            'address' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $warehouse->update($request->only([
            'code', 'name', 'address', 'latitude', 'longitude',
            'capacity_sqm', 'type', 'pic_name', 'pic_phone', 'pic_email',
            'operational_hours', 'is_active',
        ]));

        return response()->json(['data' => $warehouse]);
    }

    public function destroy(string|int $warehouse): JsonResponse
    {
        $warehouse = Warehouse::findOrFail($warehouse);
        $warehouse->delete();
        return response()->json(['message' => 'Warehouse deleted']);
    }

    public function summary(string|int $warehouse): JsonResponse
    {
        $warehouse = Warehouse::with(['zones', 'planogram'])->findOrFail($warehouse);

        $zoneCount = $warehouse->zones->count();
        $rackCount = $warehouse->zones->flatMap->racks->count();

        return response()->json(['data' => [
            'warehouse_id' => $warehouse->id,
            'zone_count' => $zoneCount,
            'rack_count' => $rackCount,
            'planogram_version' => $warehouse->planogram->version ?? null,
        ]]);
    }

    public function utilization(string|int $warehouse): JsonResponse
    {
        $warehouse = Warehouse::with(['zones.racks.levels.slots'])->findOrFail($warehouse);

        $zones = $warehouse->zones;
        $totalSlots = 0;
        $filledSlots = 0;

        foreach ($zones as $zone) {
            foreach ($zone->racks as $rack) {
                foreach ($rack->levels as $level) {
                    foreach ($level->slots as $slot) {
                        $totalSlots++;
                        if ($slot->fixed_product_id) $filledSlots++;
                    }
                }
            }
        }

        return response()->json(['data' => [
            'warehouse_id' => $warehouse->id,
            'total_slots' => $totalSlots,
            'filled_slots' => $filledSlots,
            'empty_slots' => $totalSlots - $filledSlots,
            'utilization_percent' => $totalSlots > 0 ? round(($filledSlots / $totalSlots) * 100, 1) : 0,
        ]]);
    }
}