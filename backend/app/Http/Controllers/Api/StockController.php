<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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
