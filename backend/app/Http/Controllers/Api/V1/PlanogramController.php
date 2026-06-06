<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\{Planogram, PlanogramSnapshot, Warehouse};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanogramController extends Controller
{
    public function show(string|int $warehouse): JsonResponse
    {
        $planogram = Planogram::where('warehouse_id', $warehouse)
            ->with('createdBy:id,name')
            ->latest()
            ->first();

        if (!$planogram) {
            return response()->json(['data' => null, 'message' => 'Planogram not found for this warehouse'], 404);
        }

        return response()->json(['data' => $planogram]);
    }

    public function update(Request $request, string|int $warehouse): JsonResponse
    {
        $request->validate([
            'canvas_data' => 'required|array',
            'canvas_settings' => 'nullable|array',
            'change_summary' => 'nullable|string|max:500',
        ]);

        $warehouse = Warehouse::findOrFail($warehouse);
        $user = $request->user();

        // Get current planogram or create new
        $planogram = Planogram::where('warehouse_id', $warehouse->id)->latest()->first();

        if ($planogram) {
            // Save snapshot of current state before updating
            PlanogramSnapshot::create([
                'planogram_id' => $planogram->id,
                'version' => $planogram->version,
                'canvas_data' => $planogram->canvas_data,
                'created_by' => $user->id,
                'change_summary' => 'Auto-snapshot before edit',
                'created_at' => $planogram->updated_at,
            ]);
        }

        $newVersion = $planogram
            ? implode('.', array_map(fn($v) => $v + 1, array_reverse(explode('.', $planogram->version))))
            : '1.0';

        $planogram = Planogram::updateOrCreate(
            ['warehouse_id' => $warehouse->id],
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
        $planogram = Planogram::where('warehouse_id', $warehouse)->latest()->firstOrFail();

        $snapshot = PlanogramSnapshot::create([
            'planogram_id' => $planogram->id,
            'version' => $planogram->version . '.snap',
            'canvas_data' => $planogram->canvas_data,
            'created_by' => $request->user()->id,
            'change_summary' => $request->change_summary ?? 'Manual snapshot',
            'created_at' => now(),
        ]);

        return response()->json(['data' => $snapshot], 201);
    }

    public function history(string|int $warehouse): JsonResponse
    {
        $planogram = Planogram::where('warehouse_id', $warehouse)->latest()->firstOrFail();
        $snapshots = $planogram->snapshots()->with('createdBy:id,name')->orderByDesc('created_at')->paginate(20);
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
            return response()->json(['data' => [], 'message' => 'Product not found'], 200);
        }

        return response()->json(['data' => $products]);
    }
}
