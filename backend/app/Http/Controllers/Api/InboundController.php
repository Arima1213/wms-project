<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Inbound;
use App\Models\InboundItem;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InboundController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Inbound::with(['warehouse', 'supplier', 'user']);
        if ($request->has('warehouse_id')) $query->where('warehouse_id', $request->warehouse_id);
        if ($request->has('status')) $query->where('status', $request->status);
        return response()->json($query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15)));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'inbound_type' => 'required|in:purchase_return,transfer_in,production_in',
            'scheduled_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.bin_id' => 'nullable|exists:bins,id',
            'items.*.batch_number' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.unit_cost' => 'nullable|numeric',
        ]);
        $ref = 'INB-' . date('Ymd') . '-' . str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        $inbound = Inbound::create([
            'reference_number' => $ref,
            'warehouse_id' => $validated['warehouse_id'],
            'supplier_id' => $validated['supplier_id'] ?? null,
            'user_id' => $request->user()->id,
            'inbound_type' => $validated['inbound_type'],
            'scheduled_date' => $validated['scheduled_date'],
            'status' => 'pending',
            'notes' => $validated['notes'] ?? null,
            'total_items' => count($validated['items']),
            'total_quantity' => collect($validated['items'])->sum('quantity'),
        ]);
        foreach ($validated['items'] as $item) {
            InboundItem::create([
                'inbound_id' => $inbound->id,
                'product_id' => $item['product_id'],
                'bin_id' => $item['bin_id'] ?? null,
                'batch_number' => $item['batch_number'] ?? null,
                'quantity' => $item['quantity'],
                'accepted_quantity' => 0,
                'rejected_quantity' => 0,
                'expiry_date' => $item['expiry_date'] ?? null,
                'unit_cost' => $item['unit_cost'] ?? 0,
            ]);
        }
        return response()->json($inbound->load(['items.product', 'warehouse', 'supplier']), 201);
    }

    public function show(Inbound $inbound): JsonResponse
    {
        $inbound->load(['items.product', 'items.bin', 'warehouse', 'supplier', 'user']);
        return response()->json($inbound);
    }

    public function receive(Request $request, Inbound $inbound): JsonResponse
    {
        if ($inbound->status !== 'pending') {
            return response()->json(['message' => 'Already received'], 422);
        }
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.inbound_item_id' => 'required|exists:inbound_items,id',
            'items.*.accepted_quantity' => 'required|numeric|min:0',
            'items.*.rejected_quantity' => 'nullable|numeric|min:0',
        ]);
        return DB::transaction(function () use ($inbound, $validated) {
            foreach ($validated['items'] as $itemData) {
                $item = InboundItem::find($itemData['inbound_item_id']);
                $item->update([
                    'accepted_quantity' => $itemData['accepted_quantity'],
                    'rejected_quantity' => $itemData['rejected_quantity'] ?? 0,
                ]);
                if ($itemData['accepted_quantity'] > 0) {
                    Stock::updateOrCreate(
                        ['product_id' => $item->product_id, 'warehouse_id' => $inbound->warehouse_id, 'bin_id' => $item->bin_id, 'batch_number' => $item->batch_number],
                        ['quantity' => DB::raw('COALESCE(quantity,0) + ' . (float)$itemData['accepted_quantity']), 'available_quantity' => DB::raw('COALESCE(available_quantity,0) + ' . (float)$itemData['accepted_quantity'])]
                    );
                }
            }
            $inbound->update(['status' => 'received', 'received_date' => Carbon::today(), 'verified_by' => auth()->id(), 'verified_at' => now()]);
            return response()->json($inbound->load(['items', 'warehouse', 'supplier']));
        });
    }

    public function destroy(Inbound $inbound): JsonResponse
    {
        if (!in_array($inbound->status, ['pending', 'cancelled'])) {
            return response()->json(['message' => 'Cannot delete'], 422);
        }
        $inbound->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
