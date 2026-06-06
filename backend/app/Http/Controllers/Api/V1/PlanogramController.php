<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\{Planogram, PlanogramSnapshot, Warehouse};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanogramController extends Controller
{
    private function getPlanogram(string|int $warehouse): Planogram|null
    {
        return Planogram::where('warehouse_id', $warehouse)->latest()->first();
    }

    public function show(string|int $warehouse): JsonResponse
    {
        $warehouseModel = Warehouse::findOrFail($warehouse);
        $planogram = $this->getPlanogram($warehouseModel->id);

        if (!$planogram) {
            return response()->json(['data' => null, 'message' => 'Planogram not found for this warehouse'], 404);
        }

        $planogram->load('createdBy:id,name');
        return response()->json(['data' => $planogram]);
    }

    public function update(Request $request, string|int $warehouse): JsonResponse
    {
        $warehouseModel = Warehouse::findOrFail($warehouse);

        $request->validate([
            'canvas_data' => 'required|array',
            'canvas_settings' => 'nullable|array',
            'change_summary' => 'nullable|string|max:500',
        ]);

        $user = $request->user();

        // Get current planogram or create new
        $planogram = $this->getPlanogram($warehouseModel->id);

        if ($planogram) {
            // Save snapshot of current state before updating
            PlanogramSnapshot::create([
                'planogram_id' => $planogram->id,
                'version' => $planogram->version,
                'canvas_data' => $planogram->canvas_data,
                'created_by' => $user->id,
                'change_summary' => 'Auto-snapshot before edit',
            ]);
            $newVersion = implode('.', array_map(fn($v) => $v + 1, array_reverse(explode('.', $planogram->version))));
        } else {
            $newVersion = '1.0';
        }

        $planogram = Planogram::updateOrCreate(
            ['warehouse_id' => $warehouseModel->id],
            [
                'canvas_data' => $request->canvas_data,
                'canvas_settings' => $request->canvas_settings ?? [],
                'created_by' => $user->id,
                'version' => $newVersion,
                'change_summary' => $request->change_summary,
            ]
        );

        return response()->json(['data' => $planogram]);
    }

    public function snapshot(Request $request, string|int $warehouse): JsonResponse
    {
        $warehouseModel = Warehouse::findOrFail($warehouse);
        $planogram = $this->getPlanogram($warehouseModel->id);

        if (!$planogram) {
            return response()->json(['message' => 'No planogram found to snapshot'], 404);
        }

        $snapshot = PlanogramSnapshot::create([
            'planogram_id' => $planogram->id,
            'version' => $planogram->version . '.snap',
            'canvas_data' => $planogram->canvas_data,
            'created_by' => $request->user()->id,
            'change_summary' => $request->change_summary ?? 'Manual snapshot',
        ]);

        return response()->json(['data' => $snapshot], 201);
    }

    public function history(string|int $warehouse): JsonResponse
    {
        $warehouseModel = Warehouse::findOrFail($warehouse);
        $planogram = $this->getPlanogram($warehouseModel->id);

        if (!$planogram) {
            return response()->json(['data' => [], 'message' => 'No planogram found'], 404);
        }

        $snapshots = $planogram->snapshots()
            ->with('createdBy:id,name')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($snapshots);
    }

    public function searchProduct(Request $request): JsonResponse
    {
        $request->validate(['q' => 'required|string|min:2']);

        $products = \App\Models\Product::where('name', 'ilike', '%' . $request->q . '%')
            ->orWhere('sku', 'ilike', '%' . $request->q . '%')
            ->orWhere('barcode', 'ilike', '%' . $request->q . '%')
            ->limit(20)
            ->get();

        if ($products->isEmpty()) {
            return response()->json(['data' => []]);
        }

        return response()->json(['data' => $products]);
    }
}
