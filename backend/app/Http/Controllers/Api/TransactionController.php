<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockTransaction;
use App\Models\SlotStock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = StockTransaction::with(['product', 'creator', 'sourceSlot', 'destSlot', 'warehouse'])->latest();

        if ($request->filled('warehouse_id')) $query->where('warehouse_id', $request->warehouse_id);
        if ($request->filled('type')) $query->where('transaction_type', $request->type);
        if ($request->filled('product_id')) $query->where('product_id', $request->product_id);
        if ($request->filled('from_date')) $query->whereDate('created_at', '>=', $request->from_date);
        if ($request->filled('to_date')) $query->whereDate('created_at', '<=', $request->to_date);

        return response()->json($query->paginate($request->get('per_page', 20)));
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(StockTransaction::with(['product', 'creator', 'sourceSlot', 'destSlot'])->findOrFail($id));
    }

    public function receive(Request $request): JsonResponse
    {
        $data = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'product_id' => 'required|exists:products,id',
            'dest_slot_id' => 'required|exists:rack_slots,id',
            'batch_id' => 'nullable|exists:product_batches,id',
            'quantity' => 'required|numeric|min:0.0001',
            'uom_id' => 'nullable|exists:uoms,id',
            'unit_cost' => 'nullable|numeric',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'expiry_date' => 'nullable|date',
        ]);

        return $this->executeTransaction('GR', $data, $request);
    }

    public function issue(Request $request): JsonResponse
    {
        $data = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'product_id' => 'required|exists:products,id',
            'source_slot_id' => 'required|exists:rack_slots,id',
            'quantity' => 'required|numeric|min:0.0001',
            'uom_id' => 'nullable|exists:uoms,id',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        return $this->executeTransaction('GI', $data, $request);
    }

    public function transfer(Request $request): JsonResponse
    {
        $data = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'product_id' => 'required|exists:products,id',
            'source_slot_id' => 'required|exists:rack_slots,id',
            'dest_slot_id' => 'required|exists:rack_slots,id',
            'quantity' => 'required|numeric|min:0.0001',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        return $this->executeTransaction('TR', $data, $request);
    }

    public function adjust(Request $request): JsonResponse
    {
        $data = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'product_id' => 'required|exists:products,id',
            'slot_id' => 'required|exists:rack_slots,id',
            'quantity' => 'required|numeric',
            'type' => 'required|in:ADJ+,ADJ-',
            'reason' => 'nullable|string',
            'reference_number' => 'nullable|string|max:100',
        ]);

        $data['dest_slot_id'] = $data['slot_id'];
        $data['source_slot_id'] = $data['slot_id'];
        if ($data['type'] === 'ADJ-') $data['quantity'] = abs($data['quantity']) * -1;

        return $this->executeTransaction($data['type'], $data, $request);
    }

    public function opname(Request $request): JsonResponse
    {
        $data = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'product_id' => 'required|exists:products,id',
            'slot_id' => 'required|exists:rack_slots,id',
            'system_quantity' => 'required|numeric|min:0',
            'actual_quantity' => 'required|numeric|min:0',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $variance = $data['actual_quantity'] - $data['system_quantity'];

        return $this->executeTransaction('SO', [
            'warehouse_id' => $data['warehouse_id'],
            'product_id' => $data['product_id'],
            'dest_slot_id' => $data['slot_id'],
            'source_slot_id' => $data['slot_id'],
            'quantity' => $variance,
            'reference_number' => $data['reference_number'] ?? null,
            'notes' => ($data['notes'] ?? '') . " | Opname: sys={$data['system_quantity']}, act={$data['actual_quantity']}",
        ], $request);
    }

    private function executeTransaction(string $type, array $data, Request $request): JsonResponse
    {
        $user = $request->user();
        $ulid = Str::ulid();

        DB::transaction(function () use ($type, $data, $user, $ulid) {
            if ($type === 'GI') {
                $cs = SlotStock::where('slot_id', $data['source_slot_id'])->where('product_id', $data['product_id'])->where('is_current', true)->first();
                if (!$cs) throw new \Exception('No stock in source slot');
                $sb = $cs->quantity;
                $cs->update(['is_current' => false]);
                StockTransaction::create([
                    'ulid' => $ulid, 'transaction_type' => $type, 'product_id' => $data['product_id'],
                    'source_slot_id' => $data['source_slot_id'], 'warehouse_id' => $data['warehouse_id'],
                    'batch_id' => $data['batch_id'] ?? $cs->batch_id ?? null,
                    'quantity' => $data['quantity'],
                    'uom_id' => $data['uom_id'] ?? $cs->uom_id ?? null,
                    'quantity_in_base_uom' => $data['quantity'],
                    'stock_before' => $sb, 'stock_after' => max(0, $sb - $data['quantity']),
                    'reference_number' => $data['reference_number'] ?? null,
                    'notes' => $data['notes'] ?? null, 'created_by' => $user->id, 'created_at' => now(),
                ]);
                return;
            }

            if ($type === 'TR') {
                $cs = SlotStock::where('slot_id', $data['source_slot_id'])->where('product_id', $data['product_id'])->where('is_current', true)->first();
                $sb = $cs ? $cs->quantity : 0;
                if ($cs) $cs->update(['is_current' => false]);
                SlotStock::create([
                    'slot_id' => $data['dest_slot_id'], 'product_id' => $data['product_id'],
                    'batch_id' => $data['batch_id'] ?? ($cs->batch_id ?? null),
                    'quantity' => $data['quantity'],
                    'uom_id' => $data['uom_id'] ?? ($cs->uom_id ?? null),
                    'quantity_in_base_uom' => $data['quantity'],
                    'unit_cost' => $cs->unit_cost ?? null,
                    'total_cost' => $cs->total_cost ?? null,
                    'expiry_date' => $cs->expiry_date ?? null, 'is_current' => true,
                ]);
                StockTransaction::create([
                    'ulid' => $ulid, 'transaction_type' => $type, 'product_id' => $data['product_id'],
                    'source_slot_id' => $data['source_slot_id'], 'dest_slot_id' => $data['dest_slot_id'],
                    'warehouse_id' => $data['warehouse_id'],
                    'batch_id' => $data['batch_id'] ?? ($cs->batch_id ?? null),
                    'quantity' => $data['quantity'],
                    'uom_id' => $data['uom_id'] ?? ($cs->uom_id ?? null),
                    'quantity_in_base_uom' => $data['quantity'],
                    'stock_before' => $sb, 'stock_after' => $sb - $data['quantity'],
                    'reference_number' => $data['reference_number'] ?? null,
                    'notes' => $data['notes'] ?? null, 'created_by' => $user->id, 'created_at' => now(),
                ]);
                return;
            }

            $qty = abs($data['quantity']);

            SlotStock::create([
                'slot_id' => $data['dest_slot_id'] ?? $data['slot_id'] ?? null,
                'product_id' => $data['product_id'],
                'batch_id' => $data['batch_id'] ?? null,
                'quantity' => $qty,
                'uom_id' => $data['uom_id'] ?? null,
                'quantity_in_base_uom' => $qty,
                'unit_cost' => $data['unit_cost'] ?? null,
                'total_cost' => isset($data['unit_cost']) ? $data['unit_cost'] * $qty : null,
                'expiry_date' => $data['expiry_date'] ?? null, 'is_current' => true,
            ]);

            StockTransaction::create([
                'ulid' => $ulid, 'transaction_type' => $type, 'product_id' => $data['product_id'],
                'dest_slot_id' => $data['dest_slot_id'] ?? $data['slot_id'] ?? null,
                'warehouse_id' => $data['warehouse_id'],
                'batch_id' => $data['batch_id'] ?? null,
                'quantity' => $qty,
                'uom_id' => $data['uom_id'] ?? null,
                'quantity_in_base_uom' => $qty,
                'stock_before' => 0, 'stock_after' => $qty,
                'unit_cost' => $data['unit_cost'] ?? null,
                'total_cost' => isset($data['unit_cost']) ? $data['unit_cost'] * $qty : null,
                'reference_number' => $data['reference_number'] ?? null,
                'notes' => $data['notes'] ?? null, 'created_by' => $user->id, 'created_at' => now(),
            ]);
        });

        return response()->json(['message' => 'Transaction recorded', 'ulid' => $ulid], 201);
    }
}
