<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SlotStock;
use App\Models\StockTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function stock(Request $request): JsonResponse
    {
        $query = SlotStock::with(['product.category', 'slot.rackLevel.rack.zone.warehouse'])
            ->where('is_current', true);

        if ($request->filled('warehouse_id')) {
            $query->whereHas('slot', fn($q) => $q->whereHas('rackLevel.rack.zone', fn($z) => $z->where('warehouse_id', $request->warehouse_id)));
        }

        if ($request->filled('category_id')) {
            $query->whereHas('product', fn($q) => $q->where('category_id', $request->category_id));
        }

        $stock = $query->get()->groupBy('product_id')->map(fn($items, $pid) => [
            'product' => $items->first()->product,
            'total_quantity' => $items->sum('quantity'),
            'total_value' => $items->sum('total_cost'),
            'warehouses' => $items->groupBy(fn($i) => $i->slot->rackLevel->rack->zone->warehouse_id)->map(fn($wi) => ['warehouse' => $wi->first()->slot->rackLevel->rack->zone->warehouse, 'qty' => $wi->sum('quantity')])->values(),
        ])->values();

        return response()->json($stock);
    }

    public function mutation(Request $request): JsonResponse
    {
        $query = StockTransaction::with(['product', 'creator', 'warehouse'])
            ->latest();

        if ($request->filled('warehouse_id')) $query->where('warehouse_id', $request->warehouse_id);
        if ($request->filled('product_id')) $query->where('product_id', $request->product_id);
        if ($request->filled('type')) $query->where('transaction_type', $request->type);
        if ($request->filled('from_date')) $query->whereDate('created_at', '>=', $request->from_date);
        if ($request->filled('to_date')) $query->whereDate('created_at', '<=', $request->to_date);

        return response()->json($query->paginate($request->get('per_page', 50)));
    }

    public function aging(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        $threshold = now()->subDays($days);

        $movements = StockTransaction::where('product_id', $request->product_id ?? '!=', 0)
            ->select('product_id', DB::raw('MAX(created_at) as last_movement'))
            ->groupBy('product_id')
            ->get()
            ->filter(fn($m) => $m->last_movement < $threshold);

        $productIds = $movements->pluck('product_id');

        $stock = SlotStock::with(['product', 'slot.rackLevel.rack.zone.warehouse'])
            ->where('is_current', true)
            ->whereIn('product_id', $productIds)
            ->get();

        return response()->json($stock);
    }

    public function expiry(Request $request): JsonResponse
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

    public function utilization(Request $request): JsonResponse
    {
        $warehouseId = $request->get('warehouse_id');

        $zones = \App\Models\Zone::with(['racks.levels.slots.currentStock'])
            ->where('warehouse_id', $warehouseId)
            ->get()
            ->map(fn($zone) => [
                'zone' => $zone,
                'total_slots' => $zone->racks->pluck('levels')->flatten()->pluck('slots')->flatten()->count(),
                'occupied_slots' => $zone->racks->pluck('levels')->flatten()->pluck('slots')->flatten()->filter(fn($s) => $s->currentStock && $s->currentStock->quantity > 0)->count(),
                'utilization_pct' => $zone->racks->pluck('levels')->flatten()->pluck('slots')->flatten()->count() > 0
                    ? round($zone->racks->pluck('levels')->flatten()->pluck('slots')->flatten()->filter(fn($s) => $s->currentStock && $s->currentStock->quantity > 0)->count() / $zone->racks->pluck('levels')->flatten()->pluck('slots')->flatten()->count() * 100, 1)
                    : 0,
            ]);

        return response()->json($zones);
    }

    public function dashboard(Request $request): JsonResponse
    {
        $warehouseId = $request->get('warehouse_id');

        $totalSku = SlotStock::where('is_current', true)
            ->when($warehouseId, fn($q) => $q->whereHas('slot', fn($s) => $s->whereHas('rackLevel.rack.zone', fn($z) => $z->where('warehouse_id', $warehouseId))))
            ->distinct('product_id')->count('product_id');

        $totalValue = SlotStock::where('is_current', true)
            ->when($warehouseId, fn($q) => $q->whereHas('slot', fn($s) => $s->whereHas('rackLevel.rack.zone', fn($z) => $z->where('warehouse_id', $warehouseId))))
            ->sum('total_cost');

        $todayTx = StockTransaction::whereDate('created_at', today())
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->count();

        $lowStockCount = \App\Models\SlotStock::where('is_current', true)
            ->when($warehouseId, fn($q) => $q->whereHas('slot', fn($s) => $s->whereHas('rackLevel.rack.zone', fn($z) => $z->where('warehouse_id', $warehouseId))))
            ->get()
            ->filter(fn($s) => $s->quantity <= ($s->product->reorder_point ?? 0))
            ->count();

        return response()->json([
            'total_sku' => $totalSku,
            'total_stock_value' => $totalValue,
            'transactions_today' => $todayTx,
            'low_stock_alerts' => $lowStockCount,
        ]);
    }
}
