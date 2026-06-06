<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Outbound;
use App\Models\OutboundItem;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class OutboundController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Outbound::with(['warehouse', 'customer', 'user']);
        if ($request->has('warehouse_id')) $query->where('warehouse_id', $request->warehouse_id);
        if ($request->has('status')) $query->where('status', $request->status);
        return response()->json($query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15)));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'customer_id' => 'nullable|exists:customers,id',
            'outbound_type' => 'required|in:sales,transfer_out,damaged',
            'order_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.bin_id' => 'nullable|exists:bins,id',
            'items.*.batch_number' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'nullable|numeric',
        ]);
        $ref = 'OUT-' . date('Ymd') . '-' . str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        $outbound = Outbound::create([
            'reference_number' => $ref,
            'warehouse_id' => $validated['warehouse_id'],
            'customer_id' => $validated['customer_id'] ?? null,
            'user_id' => $request->user()->id,
            'outbound_type' => $validated['outbound_type'],
            'order_date' => $validated['order_date'],
            'status' => 'pending',
            'notes' => $validated['notes'] ?? null,
            'total_items' => count($validated['items']),
            'total_quantity' => collect($validated['items'])->sum('quantity'),
        ]);
        foreach ($validated['items'] as $item) {
            OutboundItem::create([
                'outbound_id' => $outbound->id,
                'product_id' => $item['product_id'],
                'bin_id' => $item['bin_id'] ?? null,
                'batch_number' => $item['batch_number'] ?? null,
                'quantity' => $item['quantity'],
                'picked_quantity' => 0,
                'unit_price' => $item['unit_price'] ?? 0,
            ]);
        }
        return response()->json($outbound->load(['items.product', 'warehouse', 'customer']), 201);
    }

    public function show(Outbound $outbound): JsonResponse
    {
        $outbound->load(['items.product', 'items.bin', 'warehouse', 'customer', 'user']);
        return response()->json($outbound);
    }

    public function ship(Request $request, Outbound $outbound): JsonResponse
    {
        if ($outbound->status !== 'picking') {
            return response()->json(['message' => 'Must be in picking status'], 422);
        }
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.outbound_item_id' => 'required|exists:outbound_items,id',
            'items.*.picked_quantity' => 'required|numeric|min:0',
        ]);
        return DB::transaction(function () use ($outbound, $validated) {
            foreach ($validated['items'] as $itemData) {
                $item = OutboundItem::find($itemData['outbound_item_id']);
                $item->update(['picked_quantity' => $itemData['picked_quantity']]);
                if ($itemData['picked_quantity'] > 0) {
                    Stock::where('product_id', $item->product_id)->where('warehouse_id', $outbound->warehouse_id)
                        ->where('bin_id', $item->bin_id)->where('batch_number', $item->batch_number)
                        ->decrement('quantity', $itemData['picked_quantity']);
                    Stock::where('product_id', $item->product_id)->where('warehouse_id', $outbound->warehouse_id)
                        ->where('bin_id', $item->bin_id)->where('batch_number', $item->batch_number)
                        ->decrement('available_quantity', $itemData['picked_quantity']);
                }
            }
            $outbound->update(['status' => 'shipped', 'shipped_date' => now(), 'shipped_by' => auth()->id()]);
            return response()->json($outbound->load(['items', 'warehouse', 'customer']));
        });
    }

    public function destroy(Outbound $outbound): JsonResponse
    {
        if (!in_array($outbound->status, ['pending', 'cancelled'])) {
            return response()->json(['message' => 'Cannot delete'], 422);
        }
        $outbound->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
