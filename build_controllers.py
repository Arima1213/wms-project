import os

BASE = 'C:/Users/ASUS/Downloads/docker-setup/wms-project/backend/app/Http/Controllers/Api'
os.makedirs(BASE, exist_ok=True)

files = {}

files['InboundController.php'] = """<?php
namespace App\\Http\\Controllers\\Api;
use App\\Http\\Controllers\\Controller;
use App\\Models\\Inbound;
use App\\Models\\InboundItem;
use App\\Models\\Stock;
use Illuminate\\Http\\Request;
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Support\\Facades\\DB;
use Carbon\\Carbon;

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
"""

files['OutboundController.php'] = """<?php
namespace App\\Http\\Controllers\\Api;
use App\\Http\\Controllers\\Controller;
use App\\Models\\Outbound;
use App\\Models\\OutboundItem;
use App\\Models\\Stock;
use Illuminate\\Http\\Request;
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Support\\Facades\\DB;

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
"""

files['StockController.php'] = """<?php
namespace App\\Http\\Controllers\\Api;
use App\\Http\\Controllers\\Controller;
use App\\Models\\Stock;
use Illuminate\\Http\\Request;
use Illuminate\\Http\\JsonResponse;

class StockController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Stock::with(['product', 'warehouse', 'bin']);
        if ($request->has('warehouse_id')) $query->where('warehouse_id', $request->warehouse_id);
        return response()->json($query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 50)));
    }

    public function byProduct(Request $request, $product): JsonResponse
    {
        return response()->json(Stock::with(['warehouse', 'bin'])->where('product_id', $product)->orderBy('available_quantity', 'desc')->get());
    }

    public function byWarehouse(Request $request, $warehouse): JsonResponse
    {
        return response()->json(Stock::with(['product', 'bin'])->where('warehouse_id', $warehouse)->orderBy('created_at', 'desc')->get());
    }

    public function opname(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'items' => 'required|array',
            'items.*.stock_id' => 'required|exists:stocks,id',
            'items.*.actual_quantity' => 'required|numeric|min:0',
        ]);
        $results = [];
        foreach ($validated['items'] as $item) {
            $stock = Stock::find($item['stock_id']);
            $diff = $item['actual_quantity'] - $stock->quantity;
            $stock->update(['quantity' => $item['actual_quantity'], 'available_quantity' => $item['actual_quantity'] - $stock->reserved_quantity]);
            $results[] = ['stock_id' => $stock->id, 'previous' => $stock->quantity, 'actual' => $item['actual_quantity'], 'difference' => $diff];
        }
        return response()->json(['message' => 'Stock opname completed', 'results' => $results]);
    }
}
"""

files['PlanogramController.php'] = """<?php
namespace App\\Http\\Controllers\\Api;
use App\\Http\\Controllers\\Controller;
use App\\Models\\Planogram;
use Illuminate\\Http\\Request;
use Illuminate\\Http\\JsonResponse;

class PlanogramController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Planogram::with(['warehouse', 'creator']);
        if ($request->has('warehouse_id')) $query->where('warehouse_id', $request->warehouse_id);
        return response()->json($query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15)));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'canvas_width' => 'nullable|integer',
            'canvas_height' => 'nullable|integer',
            'canvas_data' => 'nullable|array',
        ]);
        $validated['created_by'] = $request->user()->id;
        $validated['version'] = 1;
        return response()->json(Planogram::create($validated), 201);
    }

    public function show(Planogram $planogram): JsonResponse
    {
        $planogram->load(['warehouse', 'creator', 'items.product', 'items.bin', 'items.rack']);
        return response()->json($planogram);
    }

    public function update(Request $request, Planogram $planogram): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'canvas_width' => 'nullable|integer',
            'canvas_height' => 'nullable|integer',
            'canvas_data' => 'nullable|array',
            'is_published' => 'nullable|boolean',
        ]);
        $planogram->update($validated);
        return response()->json($planogram);
    }

    public function destroy(Planogram $planogram): JsonResponse
    {
        $planogram->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function canvas(Planogram $planogram): JsonResponse
    {
        $planogram->load(['items.product', 'items.bin', 'items.rack']);
        return response()->json([
            'planogram' => $planogram,
            'canvas' => [
                'width' => $planogram->canvas_width ?? 1200,
                'height' => $planogram->canvas_height ?? 800,
                'data' => $planogram->canvas_data ?? [],
                'items' => $planogram->items,
            ],
        ]);
    }
}
"""

files['ReportController.php'] = """<?php
namespace App\\Http\\Controllers\\Api;
use App\\Http\\Controllers\\Controller;
use App\\Models\\Stock;
use App\\Models\\Inbound;
use App\\Models\\Outbound;
use Illuminate\\Http\\Request;
use Illuminate\\Http\\JsonResponse;
use Carbon\\Carbon;
use Illuminate\\Support\\Facades\\DB;

class ReportController extends Controller
{
    public function stock(Request $request): JsonResponse
    {
        $query = Stock::with(['product.category', 'warehouse', 'bin']);
        if ($request->has('warehouse_id')) $query->where('warehouse_id', $request->warehouse_id);
        $stocks = $query->orderBy('product_id')->get();
        $totalValue = $stocks->sum(fn($s) => $s->quantity * ($s->product->unit_cost ?? 0));
        return response()->json([
            'stocks' => $stocks,
            'summary' => ['total_records' => $stocks->count(), 'total_quantity' => $stocks->sum('quantity'), 'total_available' => $stocks->sum('available_quantity'), 'total_value' => $totalValue],
        ]);
    }

    public function inbound(Request $request): JsonResponse
    {
        $start = $request->get('start_date', Carbon::now()->startOfMonth());
        $end = $request->get('end_date', Carbon::now());
        $query = Inbound::with(['warehouse', 'supplier'])->whereBetween('received_date', [$start, $end]);
        if ($request->has('warehouse_id')) $query->where('warehouse_id', $request->warehouse_id);
        $inbounds = $query->orderBy('received_date', 'desc')->get();
        return response()->json(['inbounds' => $inbounds, 'summary' => ['total_records' => $inbounds->count(), 'total_quantity' => $inbounds->sum('total_quantity'), 'by_status' => $inbounds->groupBy('status')->map->count()]]);
    }

    public function outbound(Request $request): JsonResponse
    {
        $start = $request->get('start_date', Carbon::now()->startOfMonth());
        $end = $request->get('end_date', Carbon::now());
        $query = Outbound::with(['warehouse', 'customer'])->whereBetween('shipped_date', [$start, $end]);
        if ($request->has('warehouse_id')) $query->where('warehouse_id', $request->warehouse_id);
        $outbounds = $query->orderBy('shipped_date', 'desc')->get();
        return response()->json(['outbounds' => $outbounds, 'summary' => ['total_records' => $outbounds->count(), 'total_quantity' => $outbounds->sum('total_quantity'), 'total_shipping_cost' => $outbounds->sum('shipping_cost'), 'by_status' => $outbounds->groupBy('status')->map->count()]]);
    }

    public function movement(Request $request): JsonResponse
    {
        $start = $request->get('start_date', Carbon::now()->startOfMonth());
        $end = $request->get('end_date', Carbon::now());
        $inbounds = Inbound::whereBetween('received_date', [$start, $end])->select('received_date as date', DB::raw('SUM(total_quantity) as total'))->groupBy('received_date')->get()->keyBy('date');
        $outbounds = Outbound::whereBetween('shipped_date', [$start, $end])->select('shipped_date as date', DB::raw('SUM(total_quantity) as total'))->groupBy('shipped_date')->get()->keyBy('date');
        $dates = $inbounds->keys()->merge($outbounds->keys())->unique()->sort();
        $movement = $dates->map(fn($date) => ['date' => $date, 'inbound' => $inbounds->get($date)->total ?? 0, 'outbound' => $outbounds->get($date)->total ?? 0, 'net' => ($inbounds->get($date)->total ?? 0) - ($outbounds->get($date)->total ?? 0)])->values();
        return response()->json($movement);
    }

    public function valuation(Request $request): JsonResponse
    {
        $stocks = Stock::with(['product', 'warehouse'])->get();
        $byWarehouse = $stocks->groupBy('warehouse_id')->map(fn($items, $id) => ['warehouse_id' => $id, 'warehouse_name' => $items->first()->warehouse->name ?? 'Unknown', 'total_value' => $items->sum(fn($s) => $s->quantity * ($s->product->unit_cost ?? 0)), 'total_items' => $items->count()])->values();
        return response()->json(['total_valuation' => $stocks->sum(fn($s) => $s->quantity * ($s->product->unit_cost ?? 0)), 'by_warehouse' => $byWarehouse]);
    }
}
"""

for fname, content in files.items():
    path = os.path.join(BASE, fname)
    with open(path, 'w') as f:
        f.write(content)
    print(f'Created: {fname}')

print('All controllers done!')