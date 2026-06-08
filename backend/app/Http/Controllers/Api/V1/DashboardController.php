<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\{Inventory, StockTransaction, Product, Warehouse, Inbound, Outbound};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $warehouseId = $request->get('warehouse_id');

        $baseQuery = fn($model) => $model::query()
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId));

        // Key metrics
        $totalSku = Product::query()->when($warehouseId, fn($q) => $q->whereHas('inventory', fn($iq) => $iq->where('warehouse_id', $warehouseId)))->count();
        $totalStockValue = Inventory::query()
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->selectRaw('SUM(quantity * unit_cost) as total')->value('total') ?? 0;

        $todayTransactions = (clone $baseQuery(new StockTransaction))
            ->whereDate('created_at', now())->count();

        $todayInbounds = (clone $baseQuery(new Inbound))
            ->whereDate('created_at', now())->count();

        $todayOutbounds = (clone $baseQuery(new Outbound))
            ->whereDate('created_at', now())->count();

        // Low stock alerts
        $lowStockAlerts = Inventory::query()
            ->join('products', 'inventory.product_id', '=', 'products.id')
            ->when($warehouseId, fn($q) => $q->where('inventory.warehouse_id', $warehouseId))
            ->whereColumn('inventory.quantity', '<=', 'products.reorder_point')
            ->where('inventory.quantity', '>', 0)
            ->count();

        // Near expiry alerts
        $nearExpiryAlerts = Inventory::query()
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now(), now()->addDays(30)])
            ->count();

        // Warehouse utilization
        $warehouses = Warehouse::withCount(['zones', 'inventory'])->get()->map(fn($w) => [
            'id' => $w->id, 'name' => $w->name, 'code' => $w->code,
            'zone_count' => $w->zones_count, 'inventory_count' => $w->inventory_count,
        ]);

        // Recent transactions
        $recentTx = StockTransaction::with(['product:id,name,sku', 'user:id,name', 'warehouse:id,name'])
            ->orderByDesc('created_at')->limit(10)->get();

        return response()->json([
            'data' => [
                'total_sku' => $totalSku,
                'total_stock_value' => round($totalStockValue, 2),
                'today_transactions' => $todayTransactions,
                'today_inbounds' => $todayInbounds,
                'today_outbounds' => $todayOutbounds,
                'low_stock_alerts' => $lowStockAlerts,
                'near_expiry_alerts' => $nearExpiryAlerts,
                'warehouses' => $warehouses,
                'recent_transactions' => $recentTx,
            ]
        ]);
    }
}
