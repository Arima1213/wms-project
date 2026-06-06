<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rack;
use App\Models\RackLevel;
use App\Models\Zone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RackController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Rack::with(['zone', 'levels.slots']);

        if ($request->filled('zone_id')) {
            $query->where('zone_id', $request->zone_id);
        }

        $racks = $query->latest()->paginate($request->get('per_page', 20));
        return response()->json($racks);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'zone_id' => 'required|exists:zones,id',
            'code' => 'required|string|max:20',
            'name' => 'nullable|string|max:255',
            'canvas_x' => 'nullable|numeric',
            'canvas_y' => 'nullable|numeric',
            'width_cm' => 'nullable|numeric',
            'depth_cm' => 'nullable|numeric',
            'height_cm' => 'nullable|numeric',
            'orientation' => 'nullable|in:horizontal,vertical',
            'max_weight_kg' => 'nullable|numeric',
            'level_count' => 'nullable|integer|min:1|max:10',
        ]);

        $rack = Rack::create($data);

        $levelCount = $data['level_count'] ?? 3;
        $zone = Zone::find($data['zone_id']);
        $zoneCode = $zone->code;
        $rackCode = $data['code'];

        for ($i = 1; $i <= $levelCount; $i++) {
            $level = $rack->levels()->create([
                'level_number' => $i,
                'height_cm' => 100,
                'is_active' => true,
            ]);

            $slotCount = 6;
            for ($s = 1; $s <= $slotCount; $s++) {
                $level->slots()->create([
                    'slot_code' => sprintf('%s-%s-L%d-S%d', $zoneCode, $rackCode, $i, $s),
                    'slot_number' => $s,
                    'is_active' => true,
                ]);
            }
        }

        $rack->load('levels.slots');
        return response()->json($rack, 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(Rack::with(['zone.warehouse', 'levels.slots.currentStock.product'])->findOrFail($id));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $rack = Rack::findOrFail($id);
        $rack->update($request->validate([
            'name' => 'nullable|string|max:255',
            'canvas_x' => 'nullable|numeric',
            'canvas_y' => 'nullable|numeric',
            'width_cm' => 'nullable|numeric',
            'depth_cm' => 'nullable|numeric',
            'height_cm' => 'nullable|numeric',
            'orientation' => 'nullable|in:horizontal,vertical',
            'max_weight_kg' => 'nullable|numeric',
        ]));
        return response()->json($rack);
    }

    public function destroy(string $id): JsonResponse
    {
        Rack::findOrFail($id)->delete();
        return response()->json(null, 204);
    }

    public function byZone(Zone $zone): JsonResponse
    {
        return response()->json($zone->racks()->with('levels.slots.currentStock')->get());
    }

    public function levels(Rack $rack): JsonResponse
    {
        return response()->json($rack->levels()->with('slots.currentStock')->orderBy('level_number')->get());
    }

    public function storeLevel(Request $request, Rack $rack): JsonResponse
    {
        $data = $request->validate([
            'level_number' => 'required|integer|min:1',
            'height_cm' => 'nullable|numeric',
            'max_weight_kg' => 'nullable|numeric',
        ]);

        $level = $rack->levels()->create($data);

        $slotCount = $request->input('slot_count', 6);
        $zone = $rack->zone;
        for ($s = 1; $s <= $slotCount; $s++) {
            $level->slots()->create([
                'slot_code' => sprintf('%s-%s-L%d-S%d', $zone->code, $rack->code, $level->level_number, $s),
                'slot_number' => $s,
                'is_active' => true,
            ]);
        }

        $level->load('slots');
        return response()->json($level, 201);
    }

    public function updateLevel(Request $request, RackLevel $level): JsonResponse
    {
        $level->update($request->validate(['height_cm' => 'nullable|numeric', 'max_weight_kg' => 'nullable|numeric', 'is_active' => 'nullable|boolean']));
        return response()->json($level);
    }

    public function destroyLevel(RackLevel $level): JsonResponse
    {
        $level->delete();
        return response()->json(null, 204);
    }
}
