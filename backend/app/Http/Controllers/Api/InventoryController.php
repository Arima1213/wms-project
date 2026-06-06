<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SlotStock;
use App\Models\StockTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SlotStock::with(['slot.rackLevel.rack.zone.warehouse', 'product', 'batch'])
            ->where('is_current', true);

        if ($request->filled('warehouse_id')) {
            $query->whereHas('slot', fn($q) => $q->whereHas('rackLevel.rack.zone', fn($z) => $z->where('warehouse_id', $request->warehouse_id)));
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        $inventory = $query->paginate($request->get('per_page', 20));
        return response()->json($inventory);
    }

    public function stockByWarehouse(Request $request): JsonResponse
    {
        $warehouseId = $request->get('warehouse_id');

        $stock = SlotStock::with(['product'])
            ->where('is_current', true)
            ->whereHas('slot', fn($q) => $q->whereHas('rackLevel.rack.zone', fn($z) => $z->where('warehouse_id', $warehouseId)))
            ->select('product_id', DB::raw('SUM(quantity) as total_quantity'), DB::raw('SUM(total_cost) as total_value'))
            ->groupBy('product_id')
            ->get();

        return response()->json($stock);
    }

    public function lowStock(Request $request): JsonResponse
    {
        $threshold = $request->get('threshold', 'reorder_point');

        $items = SlotStock::with(['product', 'slot.rackLevel.rack.zone.warehouse'])
            ->where('is_current', true)
            ->whereHas('product', fn($q) => $q->whereColumn('reorder_point', '>=', 'min_stock'))
            ->get()
            ->filter(fn($item) => $item->quantity <= $item->product->{$threshold})
            ->values();

        return response()->json($items);
    }

    public function expiring(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);

        $items = SlotStock::with(['product', 'batch', 'slot.rackLevel.rack.zone.warehouse'])
            ->where('is_current', true)
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', now()->addDays($days))
            ->orderBy('expiry_date')
            ->get();

        return response()->json($items);
    }
}
