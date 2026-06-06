<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\StockOpname;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockOpnameController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = StockOpname::with('warehouse', 'user');
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        $opnames = $query->orderByDesc('created_at')->paginate($request->get('per_page', 25));
        return response()->json($opnames);
    }

    public function show(string|int $opname): JsonResponse
    {
        $opname = StockOpname::with(['warehouse', 'user', 'items.product'])->findOrFail($opname);
        return response()->json(['data' => $opname]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'opname_number' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $opname = StockOpname::create([
            'opname_number' => $validated['opname_number'] ?? 'SO-' . date('Ymd') . '-' . str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT),
            'warehouse_id' => $validated['warehouse_id'],
            'user_id' => $request->user()->id,
            'status' => 'draft',
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json(['data' => $opname], 201);
    }

    public function start(Request $request, string|int $opname): JsonResponse
    {
        $opname = StockOpname::findOrFail($opname);
        if ($opname->status !== 'draft') {
            return response()->json(['message' => 'Cannot start this stock opname'], 422);
        }
        $opname->update(['status' => 'in_progress']);
        return response()->json(['data' => $opname]);
    }

    public function update(Request $request, string|int $opnameId): JsonResponse
    {
        $opname = StockOpname::findOrFail($opnameId);
        
        $validated = $request->validate([
            'notes' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.system_qty' => 'required|numeric',
            'items.*.actual_qty' => 'required|numeric',
            'items.*.difference_qty' => 'required|numeric',
        ]);

        if (isset($validated['notes'])) {
            $opname->update(['notes' => $validated['notes']]);
        }

        if (isset($validated['items'])) {
            // Clear existing items and recreate
            $opname->items()->delete();
            foreach ($validated['items'] as $item) {
                $opname->items()->create($item);
            }
        }

        return response()->json(['data' => $opname->load('items')]);
    }

    public function submit(Request $request, string|int $opname): JsonResponse
    {
        $opname = StockOpname::findOrFail($opname);
        if ($opname->status !== 'in_progress') {
            return response()->json(['message' => 'Cannot submit this stock opname'], 422);
        }
        $opname->update(['status' => 'submitted', 'submitted_at' => now()]);
        return response()->json(['data' => $opname]);
    }

    public function approve(Request $request, string|int $opname): JsonResponse
    {
        $opname = StockOpname::findOrFail($opname);
        if ($opname->status !== 'submitted') {
            return response()->json(['message' => 'Cannot approve this stock opname'], 422);
        }
        $opname->update(['status' => 'approved', 'approved_at' => now()]);
        return response()->json(['data' => $opname]);
    }
}