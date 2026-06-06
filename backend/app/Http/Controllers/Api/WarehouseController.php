<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Models\Zone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Warehouse::query();

        if ($request->has('search')) {
            $query->where('name', 'ilike', '%' . $request->search . '%')
                  ->orWhere('code', 'ilike', '%' . $request->search . '%');
        }

        if ($request->filled('type')) {
            $query->where('warehouse_type', $request->type);
        }

        if ($request->boolean('active_only')) {
            $query->where('is_active', true);
        }

        $warehouses = $query->with(['zones'])->latest()->paginate($request->get('per_page', 15));

        return response()->json($warehouses);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => 'required|string|max:20|unique:warehouses,code',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'capacity_m2' => 'nullable|numeric',
            'pic_name' => 'nullable|string|max:255',
            'pic_phone' => 'nullable|string|max:20',
            'operating_hours' => 'nullable|array',
            'warehouse_type' => 'nullable|in:reguler,cold_storage,bonded,konsinyasi',
        ]);

        $warehouse = Warehouse::create($data);

        return response()->json($warehouse, 201);
    }

    public function show(string $id): JsonResponse
    {
        $warehouse = Warehouse::with(['zones.racks.levels.slots'])->findOrFail($id);
        return response()->json($warehouse);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $warehouse = Warehouse::findOrFail($id);
        $warehouse->update($request->validated());
        return response()->json($warehouse);
    }

    public function destroy(string $id): JsonResponse
    {
        Warehouse::findOrFail($id)->delete();
        return response()->json(null, 204);
    }

    public function zones(Warehouse $warehouse): JsonResponse
    {
        return response()->json($warehouse->zones()->with('racks')->get());
    }

    public function storeZone(Request $request, Warehouse $warehouse): JsonResponse
    {
        $data = $request->validate([
            'code' => 'required|string|max:10|unique:zones,code,NULL,id,warehouse_id,' . $warehouse->id,
            'name' => 'required|string|max:255',
            'zone_type' => 'nullable|in:fast_moving,slow_moving,heavy,cold,hazmat',
            'color' => 'nullable|string|max:7',
            'description' => 'nullable|string',
        ]);

        $zone = $warehouse->zones()->create($data);
        return response()->json($zone, 201);
    }

    public function updateZone(Request $request, Zone $zone): JsonResponse
    {
        $zone->update($request->validate([
            'name' => 'nullable|string|max:255',
            'zone_type' => 'nullable|in:fast_moving,slow_moving,heavy,cold,hazmat',
            'color' => 'nullable|string|max:7',
            'description' => 'nullable|string',
            'temperature_range' => 'nullable|array',
            'humidity_range' => 'nullable|array',
            'allowed_product_types' => 'nullable|array',
        ]));
        return response()->json($zone);
    }

    public function destroyZone(Zone $zone): JsonResponse
    {
        $zone->delete();
        return response()->json(null, 204);
    }
}
