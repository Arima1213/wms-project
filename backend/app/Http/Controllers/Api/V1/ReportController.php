<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\{Inventory, InventoryTransaction, Product, Warehouse};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportController extends Controller
{
    public function stock(Request $request): JsonResponse
    {
        $query = Inventory::with(['product:id,sku,name,category_id', 'product.category:id,name', 'warehouse:id,code,name'])
            ->when($request->warehouse_id, fn($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->category_id, fn($q) => $q->whereHas('product', fn($pq) => $pq->where('category_id', $request->category_id)));

        $data = $query->paginate($request->get('per_page', 50));
        return response()->json($data);
    }

    public function mutations(Request $request): JsonResponse
    {
        $query = InventoryTransaction::with(['product:id,sku,name', 'warehouse:id,code,name', 'user:id,name'])
            ->when($request->warehouse_id, fn($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->product_id, fn($q) => $q->where('product_id', $request->product_id))
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->from, fn($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->to, fn($q) => $q->whereDate('created_at', '<=', $request->to))
            ->orderByDesc('created_at');

        $data = $query->paginate($request->get('per_page', 50));
        return response()->json($data);
    }

    public function aging(Request $request): JsonResponse
    {
        $days = (int) $request->get('days', 30);
        $warehouseId = $request->warehouse_id;

        // Products that haven't moved in N days
        $cutoff = now()->subDays($days);

        $aging = DB::table('inventory as i')
            ->join('products as p', 'i.product_id', '=', 'p.id')
            ->join('warehouses as w', 'i.warehouse_id', '=', 'w.id')
            ->select('p.id', 'p.sku', 'p.name', 'w.id as warehouse_id', 'w.code as warehouse_code',
                DB::raw('SUM(i.quantity) as total_qty'),
                DB::raw("MAX(it.created_at) as last_movement"),
                DB::raw("EXTRACT(DAY FROM NOW() - MAX(it.created_at)) as days_since_movement"))
            ->leftJoin('inventory_transactions as it', fn($j) => $j->on('i.product_id', '=', 'it.product_id')->on('i.warehouse_id', '=', 'it.warehouse_id'))
            ->when($warehouseId, fn($q) => $q->where('i.warehouse_id', $warehouseId))
            ->where('i.quantity', '>', 0)
            ->groupBy('p.id', 'p.sku', 'p.name', 'w.id', 'w.code')
            ->having(DB::raw("MAX(it.created_at)"), '<', $cutoff)
            ->orderByDesc('days_since_movement')
            ->paginate(50);

        return response()->json($aging);
    }

    public function expiry(Request $request): JsonResponse
    {
        $within = (int) $request->get('within_days', 30);
        $warehouseId = $request->warehouse_id;

        $data = Inventory::with(['product:id,sku,name', 'warehouse:id,code,name'])
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now(), now()->addDays($within)])
            ->orderBy('expiry_date')
            ->paginate(50);

        return response()->json($data);
    }

    public function utilization(Request $request): JsonResponse
    {
        $warehouseId = $request->warehouse_id;
        $warehouseQuery = Warehouse::query()->when($warehouseId, fn($q) => $q->where('id', $warehouseId));

        $warehouses = $warehouseQuery->with(['zones.racks.levels.slots'])->get();

        $result = $warehouses->map(fn($w) => [
            'warehouse' => ['id' => $w->id, 'code' => $w->code, 'name' => $w->name],
            'total_zones' => $w->zones->count(),
            'total_racks' => $w->zones->flatMap(fn($z) => $z->racks)->count(),
            'total_slots' => $w->zones->flatMap(fn($z) => $z->racks->flatMap(fn($r) => $r->levels->flatMap(fn($l) => $l->slots))),
            'utilization' => $w->zones->flatMap(fn($z) => $z->racks->flatMap(fn($r) => $r->levels->flatMap(fn($l) => $l->slots)))
                ->filter(fn($s) => $s->status !== 'empty')->count(),
        ]);

        return response()->json(['data' => $result]);
    }

    public function activity(Request $request): JsonResponse
    {
        $data = InventoryTransaction::with(['user:id,name', 'warehouse:id,code,name', 'product:id,sku,name'])
            ->when($request->warehouse_id, fn($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->orderByDesc('created_at')
            ->paginate($request->get('per_page', 50));

        return response()->json($data);
    }

    public function export(Request $request): JsonResponse
    {
        // Returns URL to exported file (handled asynchronously via queue)
        $type = $request->get('type', 'stock');
        $format = $request->get('format', 'xlsx');

        // TODO: Dispatch export job to queue
        return response()->json([
            'message' => 'Export job queued',
            'type' => $type, 'format' => $format,
        ]);
    }
}
