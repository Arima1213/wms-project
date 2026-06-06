<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Transfer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransferController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Transfer::with('sourceWarehouse', 'destWarehouse', 'user');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $transfers = $query->orderByDesc('created_at')->paginate($request->get('per_page', 25));
        return response()->json($transfers);
    }

    public function show(string|int $transfer): JsonResponse
    {
        $transfer = Transfer::with(['sourceWarehouse', 'destWarehouse', 'user', 'items.product'])->findOrFail($transfer);
        return response()->json(['data' => $transfer]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source_warehouse_id' => 'required|exists:warehouses,id',
            'dest_warehouse_id' => 'required|exists:warehouses,id|different:source_warehouse_id',
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'nullable|array',
        ]);

        $transfer = Transfer::create([
            'transfer_number' => 'TRF-' . date('Ymd') . '-' . str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT),
            'source_warehouse_id' => $validated['source_warehouse_id'],
            'dest_warehouse_id' => $validated['dest_warehouse_id'],
            'user_id' => $request->user()->id,
            'status' => 'pending',
            'reason' => $validated['reason'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        if (!empty($validated['items'])) {
            foreach ($validated['items'] as $item) {
                $transfer->items()->create($item);
            }
        }

        return response()->json(['data' => $transfer->load('items')], 201);
    }

    public function approve(Request $request, string|int $transfer): JsonResponse
    {
        $transfer = Transfer::findOrFail($transfer);
        if ($transfer->status !== 'pending') {
            return response()->json(['message' => 'Cannot approve this transfer'], 422);
        }
        $transfer->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $request->user()->id,
        ]);
        return response()->json(['data' => $transfer]);
    }

    public function reject(Request $request, string|int $transfer): JsonResponse
    {
        $transfer = Transfer::findOrFail($transfer);
        if ($transfer->status !== 'pending') {
            return response()->json(['message' => 'Cannot reject this transfer'], 422);
        }
        $transfer->update(['status' => 'rejected']);
        return response()->json(['data' => $transfer]);
    }

    public function execute(Request $request, string|int $transfer): JsonResponse
    {
        $transfer = Transfer::findOrFail($transfer);
        if ($transfer->status !== 'approved') {
            return response()->json(['message' => 'Only approved transfers can be executed'], 422);
        }
        $transfer->update([
            'status' => 'executed',
            'received_at' => now(),
            'received_by' => $request->user()->id,
        ]);
        return response()->json(['data' => $transfer]);
    }
}