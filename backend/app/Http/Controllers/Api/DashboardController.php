<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Models\StockTransaction;
use App\Models\SlotStock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $warehouseId = $request->get('warehouse_id');

        $totalWarehouses = Warehouse::when($warehouseId, fn($q) => $q->where('id', $warehouseId))->count();
        $totalProducts = SlotStock::where('is_current', true)->distinct('product_id')->count('product_id');
        $totalValue = SlotStock::where('is_current', true)->sum('total_cost');
        $todayTx = StockTransaction::whereDate('created_at', today())->count();

        $recentTx = StockTransaction::with(['product', 'creator'])
            ->latest()
            ->limit(10)
            ->get();

        return response()->json([
            'total_warehouses' => $totalWarehouses,
            'total_products' => $totalProducts,
            'total_stock_value' => $totalValue,
            'transactions_today' => $todayTx,
            'recent_transactions' => $recentTx,
        ]);
    }
}
