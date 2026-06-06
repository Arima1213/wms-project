<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlanogramLayout;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanogramController extends Controller
{
    public function show(Warehouse $warehouse): JsonResponse
    {
        $layout = PlanogramLayout::firstOrCreate(
            ['warehouse_id' => $warehouse->id],
            ['canvas_width' => 5000, 'canvas_height' => 3000, 'grid_size' => 50, 'version' => 1]
        );

        $racks = $warehouse->zones()->with(['racks' => function ($q) {
            $q->with(['levels.slots.currentStock.product', 'levels.slots' => function ($sq) {
                $sq->where('is_active', true);
            }]);
        }])->get();

        return response()->json([
            'layout' => $layout,
            'racks' => $racks,
            'zones' => $warehouse->zones()->select(['id', 'code', 'name', 'color'])->get(),
        ]);
    }

    public function update(Request $request, Warehouse $warehouse): JsonResponse
    {
        $layout = PlanogramLayout::firstOrCreate(
            ['warehouse_id' => $warehouse->id],
            ['canvas_width' => 5000, 'canvas_height' => 3000, 'grid_size' => 50, 'version' => 1]
        );

        $data = $request->validate([
            'canvas_width' => 'nullable|integer',
            'canvas_height' => 'nullable|integer',
            'grid_size' => 'nullable|integer',
            'layout_data' => 'nullable|array',
            'racks' => 'nullable|array',
        ]);

        $data['version'] = $layout->version + 1;

        $layout->update($data);

        if ($request->has('racks')) {
            foreach ($request->racks as $rackData) {
                if (isset($rackData['id'])) {
                    \App\Models\Rack::where('id', $rackData['id'])->update([
                        'canvas_x' => $rackData['canvas_x'] ?? null,
                        'canvas_y' => $rackData['canvas_y'] ?? null,
                    ]);
                }
            }
        }

        return response()->json($layout->fresh());
    }
}
