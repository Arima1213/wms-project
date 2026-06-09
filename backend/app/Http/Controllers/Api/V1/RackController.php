<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Rack;
use App\Models\RackLevel;
use App\Models\Zone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RackController extends Controller
{
    /**
     * GET /v1/zones/{zone}/racks
     */
    public function index(string|int $zone): JsonResponse
    {
        $racks = Rack::where('zone_id', $zone)
            ->withCount('levels', 'slots')
            ->orderBy('code')
            ->paginate(50);
        return response()->json($racks);
    }

    /**
     * POST /v1/zones/{zone}/racks
     */
    public function store(Request $request, string|int $zone): JsonResponse
    {
        Zone::findOrFail($zone);

        $request->validate([
            'code' => 'required|string|max:20',
            'name' => 'nullable|string|max:100',
            'pos_x' => 'nullable|numeric',
            'pos_y' => 'nullable|numeric',
            'width' => 'nullable|numeric',
            'depth' => 'nullable|numeric',
            'max_weight_per_kg' => 'nullable|numeric',
        ]);

        $rack = Rack::create([
            'zone_id' => $zone,
            'code' => $request->code,
            'name' => $request->name,
            'canvas_x' => $request->pos_x ?? 0,
            'canvas_y' => $request->pos_y ?? 0,
            'width_cm' => $request->width ?? 4,
            'depth_cm' => $request->depth ?? 2,
            'max_weight_kg' => $request->max_weight_per_kg ?? 500,
            'is_active' => true,
        ]);

        // Auto-create a default level so slots can be added immediately
        RackLevel::create([
            'rack_id' => $rack->id,
            'level_number' => 1,
            'is_active' => true,
        ]);

        $rack->load('levels');

        return response()->json(['data' => $rack], 201);
    }

    /**
     * GET /v1/zones/{zone}/racks/{rack}
     */
    public function show(string|int $zone, string|int $rack): JsonResponse
    {
        $rack = Rack::where('zone_id', $zone)
            ->where('id', $rack)
            ->with('levels.slots')
            ->firstOrFail();
        return response()->json(['data' => $rack]);
    }

    /**
     * PUT/PATCH /v1/zones/{zone}/racks/{rack}
     */
    public function update(Request $request, string|int $zone, string|int $rack): JsonResponse
    {
        $rack = Rack::where('zone_id', $zone)
            ->where('id', $rack)
            ->firstOrFail();

        $request->validate([
            'code' => 'sometimes|required|string|max:20',
            'name' => 'nullable|string|max:100',
            'pos_x' => 'nullable|numeric',
            'pos_y' => 'nullable|numeric',
            'width' => 'nullable|numeric',
            'depth' => 'nullable|numeric',
            'max_weight_per_kg' => 'nullable|numeric',
        ]);

        $updates = [];
        if ($request->has('code')) $updates['code'] = $request->code;
        if ($request->has('name')) $updates['name'] = $request->name;
        if ($request->has('pos_x')) $updates['canvas_x'] = $request->pos_x;
        if ($request->has('pos_y')) $updates['canvas_y'] = $request->pos_y;
        if ($request->has('width')) $updates['width_cm'] = $request->width;
        if ($request->has('depth')) $updates['depth_cm'] = $request->depth;
        if ($request->has('max_weight_per_kg')) $updates['max_weight_kg'] = $request->max_weight_per_kg;

        $rack->update($updates);

        return response()->json(['data' => $rack]);
    }

    /**
     * DELETE /v1/zones/{zone}/racks/{rack}
     */
    public function destroy(string|int $zone, string|int $rack): JsonResponse
    {
        $rack = Rack::where('zone_id', $zone)
            ->where('id', $rack)
            ->firstOrFail();
        $rack->delete();
        return response()->json(['message' => 'Rack deleted']);
    }

    /**
     * GET /v1/racks/{rack}/slots
     */
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

    /**
     * PUT /v1/racks/{rack}/position
     */
    public function updatePosition(Request $request, string|int $rack): JsonResponse
    {
        $request->validate([
            'pos_x' => 'required|numeric',
            'pos_y' => 'required|numeric',
        ]);

        $rack = Rack::findOrFail($rack);
        $rack->update([
            'canvas_x' => $request->pos_x,
            'canvas_y' => $request->pos_y,
        ]);

        return response()->json(['data' => $rack]);
    }
}
