<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\{Inventory, InventoryTransaction, Product};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Inventory::with(['product:id,sku,name,barcode', 'warehouse:id,code,name', 'rackSlot:id,slot_code'])
            ->when($request->warehouse_id, fn($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->product_id, fn($q) => $q->where('product_id', $request->product_id))
            ->when($request->has('low_stock'), fn($q) => $q->whereColumn('quantity', '<=', 'product.reorder_point'));

        $data = $query->paginate($request->get('per_page', 20));
        return response()->json($data);
    }

    public function stock(Request $request): JsonResponse
    {
        $query = Product::withSum(['inventory as total_qty' => fn($q) => $request->warehouse_id ? $q->where('warehouse_id', $request->warehouse_id) : null], 'quantity')
            ->withSum(['inventory as reserved_qty' => fn($q) => $request->warehouse_id ? $q->where('warehouse_id', $request->warehouse_id) : null], 'reserved_quantity');

        $data = $query->paginate($request->get('per_page', 20));
        return response()->json($data);
    }

    public function alerts(Request $request): JsonResponse
    {
        $warehouseId = $request->warehouse_id;
        $lowStock = Inventory::with('product')
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->whereColumn('quantity', '<=', 'product.reorder_point')
            ->get();

        $nearExpiry = Inventory::with('product')
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now(), now()->addDays(30)])
            ->get();

        $outOfStock = Inventory::with('product')
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->where('quantity', '<=', 0)
            ->get();

        return response()->json([
            'low_stock' => $lowStock,
            'near_expiry' => $nearExpiry,
            'out_of_stock' => $outOfStock,
        ]);
    }

    public function trace(Request $request, string $sku): JsonResponse
    {
        $product = Product::where('sku', $sku)->orWhere('barcode', $sku)->firstOrFail();
        $locations = Inventory::with(['warehouse:id,code,name', 'rackSlot:id,slot_code', 'rackSlot.rackLevel.rack:id,code,name'])
            ->where('product_id', $product->id)
            ->where('quantity', '>', 0)
            ->get();

        return response()->json([
            'product' => $product,
            'locations' => $locations,
        ]);
    }
}
