<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\InventoryResource;
use App\Models\{Inventory, StockTransaction, Product, Warehouse};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\{
    Alignment,
    Border,
    Fill,
    Font,
};
use Dompdf\Dompdf;

class ReportController extends Controller
{
    /**
     * Enforce a maximum per_page cap to prevent abuse and OOM.
     */
    private function maxPerPage(Request $request, int $default = 50): int
    {
        return min((int) $request->get('per_page', $default), 100);
    }

    public function stock(Request $request): JsonResponse
    {
        $ttl = 300; // 5 minutes — stock can change frequently
        $cacheKey = 'report:stock:' . md5(json_encode($request->only(['warehouse_id', 'category_id', 'per_page', 'page'])));

        $data = Cache::remember($cacheKey, $ttl, function () use ($request) {
            $query = Inventory::with(['product:id,sku,name,category_id', 'product.category:id,name', 'warehouse:id,code,name'])
                ->when($request->warehouse_id, fn($q) => $q->where('warehouse_id', $request->warehouse_id))
                ->when($request->category_id, fn($q) => $q->whereHas('product', fn($pq) => $pq->where('category_id', $request->category_id)));

            return $query->paginate($this->maxPerPage($request));
        });

        return InventoryResource::collection($data);
    }

    public function mutations(Request $request): JsonResponse
    {
        $ttl = 300; // 5 minutes
        $cacheKey = 'report:mutations:' . md5(json_encode($request->only(['warehouse_id', 'product_id', 'type', 'from', 'to', 'per_page', 'page'])));

        $data = Cache::remember($cacheKey, $ttl, function () use ($request) {
            $query = StockTransaction::with(['product:id,sku,name', 'warehouse:id,code,name', 'creator:id,name'])
                ->when($request->warehouse_id, fn($q) => $q->where('warehouse_id', $request->warehouse_id))
                ->when($request->product_id, fn($q) => $q->where('product_id', $request->product_id))
                ->when($request->type, fn($q) => $q->where('transaction_type', $request->type))
                ->when($request->from, fn($q) => $q->whereDate('created_at', '>=', $request->from))
                ->when($request->to, fn($q) => $q->whereDate('created_at', '<=', $request->to))
                ->orderByDesc('created_at');

            return $query->paginate($this->maxPerPage($request));
        });

        return response()->json($data);
    }

    public function aging(Request $request): JsonResponse
    {
        $days = (int) $request->get('days', 30);
        $warehouseId = $request->warehouse_id;

        $ttl = 900; // 15 minutes — aging data changes slowly
        $cacheKey = 'report:aging:' . md5(json_encode($request->only(['days', 'warehouse_id', 'per_page', 'page'])));

        $aging = Cache::remember($cacheKey, $ttl, function () use ($days, $warehouseId, $request) {
            // Products that haven't moved in N days
            $cutoff = now()->subDays($days);

            return DB::table('inventory as i')
                ->join('products as p', 'i.product_id', '=', 'p.id')
                ->join('warehouses as w', 'i.warehouse_id', '=', 'w.id')
                ->select('p.id', 'p.sku', 'p.name', 'w.id as warehouse_id', 'w.code as warehouse_code',
                    DB::raw('SUM(i.quantity) as total_qty'),
                    DB::raw("MAX(it.created_at) as last_movement"),
                    DB::raw("EXTRACT(DAY FROM NOW() - MAX(it.created_at)) as days_since_movement"))
                ->leftJoin('stock_transactions as it', fn($j) => $j->on('i.product_id', '=', 'it.product_id')->on('i.warehouse_id', '=', 'it.warehouse_id'))
                ->when($warehouseId, fn($q) => $q->where('i.warehouse_id', $warehouseId))
                ->whereNull('p.deleted_at')
                ->whereNull('w.deleted_at')
                ->where('i.quantity', '>', 0)
                ->groupBy('p.id', 'p.sku', 'p.name', 'w.id', 'w.code')
                ->having(DB::raw("MAX(it.created_at)"), '<', $cutoff)
                ->orderByDesc('days_since_movement')
                ->paginate($this->maxPerPage($request));
        });

        return response()->json($aging);
    }

    public function expiry(Request $request): JsonResponse
    {
        $within = (int) $request->get('within_days', 30);
        $warehouseId = $request->warehouse_id;

        $ttl = 900; // 15 minutes
        $cacheKey = 'report:expiry:' . md5(json_encode($request->only(['within_days', 'warehouse_id', 'per_page', 'page'])));

        $data = Cache::remember($cacheKey, $ttl, function () use ($within, $warehouseId, $request) {
            return Inventory::with(['product:id,sku,name', 'warehouse:id,code,name'])
                ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
                ->whereNotNull('expiry_date')
                ->whereBetween('expiry_date', [now(), now()->addDays($within)])
                ->orderBy('expiry_date')
                ->paginate($this->maxPerPage($request));
        });

        return InventoryResource::collection($data);
    }

    public function utilization(Request $request): JsonResponse
    {
        $warehouseId = $request->warehouse_id;

        $ttl = 900; // 15 minutes — warehouse structure rarely changes
        $cacheKey = 'report:utilization:' . md5(json_encode($request->only(['warehouse_id'])));

        $result = Cache::remember($cacheKey, $ttl, function () use ($warehouseId) {
            $result = collect();

            $warehouseQuery = Warehouse::query()->when($warehouseId, fn($q) => $q->where('id', $warehouseId));

            // Use chunk() to avoid loading the entire nested hierarchy into memory
            $warehouseQuery->chunk(10, function ($warehouses) use (&$result) {
                $warehouseIds = $warehouses->pluck('id');

                // Single aggregate query per batch — far more memory-efficient
                // than loading all zones→racks→levels→slots into Eloquent objects
                $stats = DB::table('zones')
                    ->select(
                        'zones.warehouse_id',
                        DB::raw('COUNT(DISTINCT zones.id) as total_zones'),
                        DB::raw('COUNT(DISTINCT racks.id) as total_racks'),
                        DB::raw('COUNT(DISTINCT rack_slots.id) as total_slots'),
                        DB::raw('COUNT(DISTINCT CASE WHEN rack_slots.status IS NULL OR rack_slots.status != \'empty\' THEN rack_slots.id END) as used_slots')
                    )
                    ->leftJoin('racks', 'racks.zone_id', '=', 'zones.id')
                    ->leftJoin('rack_levels', 'rack_levels.rack_id', '=', 'racks.id')
                    ->leftJoin('rack_slots', 'rack_slots.rack_level_id', '=', 'rack_levels.id')
                    ->whereIn('zones.warehouse_id', $warehouseIds)
                    ->groupBy('zones.warehouse_id')
                    ->get()
                    ->keyBy('warehouse_id');

                foreach ($warehouses as $w) {
                    $s = $stats->get($w->id);
                    $result->push([
                        'warehouse' => ['id' => $w->id, 'code' => $w->code, 'name' => $w->name],
                        'total_zones' => $s ? (int) $s->total_zones : 0,
                        'total_racks' => $s ? (int) $s->total_racks : 0,
                        'total_slots' => $s ? (int) $s->total_slots : 0,
                        'utilization' => $s ? (int) $s->used_slots : 0,
                    ]);
                }
            });

            return $result;
        });

        return response()->json(['data' => $result]);
    }

    public function activity(Request $request): JsonResponse
    {
        $ttl = 300; // 5 minutes
        $cacheKey = 'report:activity:' . md5(json_encode($request->only(['warehouse_id', 'per_page', 'page'])));

        $data = Cache::remember($cacheKey, $ttl, function () use ($request) {
            return StockTransaction::with(['creator:id,name', 'warehouse:id,code,name', 'product:id,sku,name'])
                ->when($request->warehouse_id, fn($q) => $q->where('warehouse_id', $request->warehouse_id))
                ->orderByDesc('created_at')
                ->paginate($this->maxPerPage($request));
        });

        return response()->json($data);
    }

    public function valuation(Request $request): JsonResponse
    {
        $warehouseId = $request->warehouse_id;

        $ttl = 900; // 15 minutes — valuation changes with stock, but not extremely volatile
        $cacheKey = 'report:valuation:' . md5(json_encode($request->only(['warehouse_id'])));

        $result = Cache::remember($cacheKey, $ttl, function () use ($warehouseId) {
            $valuation = DB::table('inventory as i')
                ->join('products as p', 'i.product_id', '=', 'p.id')
                ->select(
                    DB::raw('SUM(i.quantity) as total_quantity'),
                    DB::raw('SUM(i.quantity * COALESCE(i.unit_cost, 0)) as total_value'),
                    DB::raw('COUNT(DISTINCT p.id) as total_products')
                )
                ->when($warehouseId, fn($q) => $q->where('i.warehouse_id', $warehouseId))
                ->where('i.quantity', '>', 0)
                ->whereNull('p.deleted_at')
                ->first();

            $byCategory = DB::table('inventory as i')
                ->join('products as p', 'i.product_id', '=', 'p.id')
                ->leftJoin('product_categories as c', 'p.category_id', '=', 'c.id')
                ->select(
                    DB::raw('COALESCE(c.name, \'Tanpa Kategori\') as category'),
                    DB::raw('SUM(i.quantity * COALESCE(i.unit_cost, 0)) as value'),
                    DB::raw('SUM(i.quantity) as quantity')
                )
                ->when($warehouseId, fn($q) => $q->where('i.warehouse_id', $warehouseId))
                ->where('i.quantity', '>', 0)
                ->whereNull('p.deleted_at')
                ->groupBy('c.name')
                ->get();

            return [
                'total_quantity' => $valuation->total_quantity ?? 0,
                'total_value' => $valuation->total_value ?? 0,
                'total_products' => $valuation->total_products ?? 0,
                'by_category' => $byCategory
            ];
        });

        return response()->json(['data' => $result]);
    }

    public function export(Request $request)
    {
        $type = $request->get('type', 'stock');
        $format = $request->get('format', 'xlsx');
        $warehouseId = $request->warehouse_id;
        $params = $request->except(['type', 'format']);

        $data = match ($type) {
            'stock' => $this->getStockData($warehouseId),
            'mutations' => $this->getMutationData($warehouseId, $params),
            'valuation' => $this->getValuationData($warehouseId),
            'aging' => $this->getAgingData($warehouseId, $params),
            'expiry' => $this->getExpiryData($warehouseId, $params),
            'activity' => $this->getActivityData($warehouseId, $params),
            'utilization' => $this->getUtilizationData($warehouseId),
            default => [],
        };

        if ($format === 'pdf') {
            $html = view('reports.export', [
                'title' => 'Laporan ' . ucfirst($type),
                'type' => $type,
                'data' => $data,
                'generated_at' => now()->format('d/m/Y H:i'),
            ])->render();

            $dompdf = new Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            return $dompdf->stream("report-{$type}.pdf", ['Attachment' => true]);
        }

        // XLSX
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan ' . ucfirst($type));

        [$headers, $rows] = $this->formatExportData($type, $data);

        // Style header
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F2937']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];

        foreach (array_values($headers) as $col => $header) {
            $colLetter = chr(65 + $col);
            $sheet->setCellValue($colLetter . '1', $header);
        }
        $sheet->getStyle('A1:' . chr(64 + count($headers)) . '1')->applyFromArray($headerStyle);

        foreach ($rows as $rowIdx => $row) {
            $excelRow = $rowIdx + 2;
            foreach (array_keys($headers) as $col => $key) {
                $colLetter = chr(65 + $col);
                $sheet->setCellValue($colLetter . $excelRow, $row[$key] ?? '');
            }
        }

        foreach (array_keys($headers) as $col => $key) {
            $colLetter = chr(65 + $col);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = tempnam(sys_get_temp_dir(), 'export_') . '.xlsx';
        $writer->save($fileName);

        return response()->download($fileName, "report-{$type}.xlsx")->deleteFileAfterSend(true);
    }

    // ─── Data helpers ──────────────────────────────────────────

    private function getStockData($warehouseId): array
    {
        $ttl = 900; // 15 minutes
        $cacheKey = 'report:export:stock:' . md5(json_encode(['warehouse_id' => $warehouseId]));

        return Cache::remember($cacheKey, $ttl, function () use ($warehouseId) {
            $query = Inventory::with(['product:id,sku,name,category_id', 'product.category:id,name', 'warehouse:id,code,name'])
                ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId));
            return $query->get()->toArray();
        });
    }

    private function getMutationData($warehouseId, array $params): array
    {
        $ttl = 600; // 10 minutes — mutations for export
        $cacheKey = 'report:export:mutations:' . md5(json_encode(['warehouse_id' => $warehouseId, 'params' => $params]));

        return Cache::remember($cacheKey, $ttl, function () use ($warehouseId, $params) {
            $query = StockTransaction::with(['product:id,sku,name', 'warehouse:id,code,name', 'creator:id,name'])
                ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
                ->when($params['type'] ?? null, fn($q) => $q->where('transaction_type', $params['type']))
                ->when($params['from'] ?? null, fn($q) => $q->whereDate('created_at', '>=', $params['from']))
                ->when($params['to'] ?? null, fn($q) => $q->whereDate('created_at', '<=', $params['to']))
                ->orderByDesc('created_at')
                ->limit(5000);
            return $query->get()->toArray();
        });
    }

    private function getValuationData($warehouseId): array
    {
        $ttl = 900; // 15 minutes
        $cacheKey = 'report:export:valuation:' . md5(json_encode(['warehouse_id' => $warehouseId]));

        return Cache::remember($cacheKey, $ttl, function () use ($warehouseId) {
            $val = DB::table('inventory as i')
                ->join('products as p', 'i.product_id', '=', 'p.id')
                ->select(
                    DB::raw('SUM(i.quantity) as total_quantity'),
                    DB::raw('SUM(i.quantity * COALESCE(i.unit_cost, 0)) as total_value'),
                    DB::raw('COUNT(DISTINCT p.id) as total_products')
                )
                ->when($warehouseId, fn($q) => $q->where('i.warehouse_id', $warehouseId))
                ->where('i.quantity', '>', 0)
                ->whereNull('p.deleted_at')
                ->first();

            $byCategory = DB::table('inventory as i')
                ->join('products as p', 'i.product_id', '=', 'p.id')
                ->leftJoin('product_categories as c', 'p.category_id', '=', 'c.id')
                ->select(
                    DB::raw('COALESCE(c.name, \'Tanpa Kategori\') as category'),
                    DB::raw('SUM(i.quantity * COALESCE(i.unit_cost, 0)) as value'),
                    DB::raw('SUM(i.quantity) as quantity')
                )
                ->when($warehouseId, fn($q) => $q->where('i.warehouse_id', $warehouseId))
                ->where('i.quantity', '>', 0)
                ->whereNull('p.deleted_at')
                ->groupBy('c.name')
                ->get();

            return [
                'summary' => [
                    'total_quantity' => $val->total_quantity ?? 0,
                    'total_value' => $val->total_value ?? 0,
                    'total_products' => $val->total_products ?? 0,
                ],
                'by_category' => $byCategory->toArray(),
            ];
        });
    }

    private function getAgingData($warehouseId, array $params): array
    {
        $ttl = 900; // 15 minutes
        $cacheKey = 'report:export:aging:' . md5(json_encode(['warehouse_id' => $warehouseId, 'params' => $params]));

        return Cache::remember($cacheKey, $ttl, function () use ($warehouseId, $params) {
            $days = (int) ($params['days'] ?? 30);
            $cutoff = now()->subDays($days);
            return DB::table('inventory as i')
                ->join('products as p', 'i.product_id', '=', 'p.id')
                ->join('warehouses as w', 'i.warehouse_id', '=', 'w.id')
                ->select('p.id', 'p.sku', 'p.name', 'w.code as warehouse_code',
                    DB::raw('SUM(i.quantity) as total_qty'),
                    DB::raw('MAX(it.created_at) as last_movement'),
                    DB::raw('EXTRACT(DAY FROM NOW() - MAX(it.created_at)) as days_since_movement'))
                ->leftJoin('stock_transactions as it', fn($j) => $j->on('i.product_id', '=', 'it.product_id')->on('i.warehouse_id', '=', 'it.warehouse_id'))
                ->when($warehouseId, fn($q) => $q->where('i.warehouse_id', $warehouseId))
                ->where('i.quantity', '>', 0)
                ->whereNull('p.deleted_at')
                ->whereNull('w.deleted_at')
                ->groupBy('p.id', 'p.sku', 'p.name', 'w.code')
                ->having(DB::raw('MAX(it.created_at)'), '<', $cutoff)
                ->orderByDesc('days_since_movement')
                ->get()
                ->toArray();
        });
    }

    private function getExpiryData($warehouseId, array $params): array
    {
        $ttl = 900; // 15 minutes
        $cacheKey = 'report:export:expiry:' . md5(json_encode(['warehouse_id' => $warehouseId, 'params' => $params]));

        return Cache::remember($cacheKey, $ttl, function () use ($warehouseId, $params) {
            $within = (int) ($params['within_days'] ?? 30);
            return Inventory::with(['product:id,sku,name', 'warehouse:id,code,name'])
                ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
                ->whereNotNull('expiry_date')
                ->whereBetween('expiry_date', [now(), now()->addDays($within)])
                ->orderBy('expiry_date')
                ->get()
                ->toArray();
        });
    }

    private function getActivityData($warehouseId, array $params): array
    {
        $ttl = 600; // 10 minutes
        $cacheKey = 'report:export:activity:' . md5(json_encode(['warehouse_id' => $warehouseId, 'params' => $params]));

        return Cache::remember($cacheKey, $ttl, function () use ($warehouseId) {
            return StockTransaction::with(['creator:id,name', 'warehouse:id,code,name', 'product:id,sku,name'])
                ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
                ->orderByDesc('created_at')
                ->limit(5000)
                ->get()
                ->toArray();
        });
    }

    private function getUtilizationData($warehouseId): array
    {
        $ttl = 900; // 15 minutes
        $cacheKey = 'report:export:utilization:' . md5(json_encode(['warehouse_id' => $warehouseId]));

        return Cache::remember($cacheKey, $ttl, function () use ($warehouseId) {
            $result = [];

            $warehouseQuery = Warehouse::query()->when($warehouseId, fn($q) => $q->where('id', $warehouseId));

            // Use chunk to avoid loading entire nested hierarchy into memory
            $warehouseQuery->chunk(10, function ($warehouses) use (&$result) {
                $warehouseIds = $warehouses->pluck('id');

                $stats = DB::table('zones')
                    ->select(
                        'zones.warehouse_id',
                        DB::raw('COUNT(DISTINCT zones.id) as total_zones'),
                        DB::raw('COUNT(DISTINCT racks.id) as total_racks'),
                        DB::raw('COUNT(DISTINCT rack_slots.id) as total_slots'),
                        DB::raw('COUNT(DISTINCT CASE WHEN rack_slots.status IS NULL OR rack_slots.status != \'empty\' THEN rack_slots.id END) as used_slots')
                    )
                    ->leftJoin('racks', 'racks.zone_id', '=', 'zones.id')
                    ->leftJoin('rack_levels', 'rack_levels.rack_id', '=', 'racks.id')
                    ->leftJoin('rack_slots', 'rack_slots.rack_level_id', '=', 'rack_levels.id')
                    ->whereIn('zones.warehouse_id', $warehouseIds)
                    ->groupBy('zones.warehouse_id')
                    ->get()
                    ->keyBy('warehouse_id');

                foreach ($warehouses as $w) {
                    $s = $stats->get($w->id);
                    $result[] = [
                        'warehouse_name' => $w->name,
                        'warehouse_code' => $w->code,
                        'total_zones' => $s ? (int) $s->total_zones : 0,
                        'total_racks' => $s ? (int) $s->total_racks : 0,
                        'total_slots' => $s ? (int) $s->total_slots : 0,
                        'utilization' => $s ? (int) $s->used_slots : 0,
                    ];
                }
            });

            return $result;
        });
    }

    private function formatExportData(string $type, array $data): array
    {
        $formatters = [
            'stock' => function () use ($data) {
                $headers = ['SKU', 'Nama Produk', 'Kategori', 'Gudang', 'Kuantitas', 'Unit Cost', 'Total Nilai'];
                $rows = array_map(fn($item) => [
                    'sku' => $item['product']['sku'] ?? '',
                    'name' => $item['product']['name'] ?? '',
                    'category' => $item['product']['category']['name'] ?? '-',
                    'warehouse' => $item['warehouse']['name'] ?? '',
                    'quantity' => $item['quantity'] ?? 0,
                    'unit_cost' => number_format($item['unit_cost'] ?? 0, 0, ',', '.'),
                    'total_value' => number_format(($item['quantity'] ?? 0) * ($item['unit_cost'] ?? 0), 0, ',', '.'),
                ], $data);
                return [$headers, $rows];
            },
            'mutations' => function () use ($data) {
                $headers = ['Tanggal', 'SKU', 'Produk', 'Gudang', 'Tipe', 'Kuantitas', 'Oleh'];
                $rows = array_map(fn($item) => [
                    'date' => $item['created_at'] ?? '',
                    'sku' => $item['product']['sku'] ?? '',
                    'product' => $item['product']['name'] ?? '',
                    'warehouse' => $item['warehouse']['name'] ?? '',
                    'type' => strtoupper($item['transaction_type'] ?? $item['type'] ?? ''),
                    'quantity' => $item['quantity'] ?? 0,
                    'user' => $item['creator']['name'] ?? $item['user']['name'] ?? 'Sistem',
                ], $data);
                return [$headers, $rows];
            },
            'valuation' => function () use ($data) {
                $headers = ['Kategori', 'Kuantitas', 'Nilai'];
                $rows = array_map(fn($cat) => [
                    'category' => $cat['category'] ?? 'Tanpa Kategori',
                    'quantity' => $cat['quantity'] ?? 0,
                    'value' => number_format($cat['value'] ?? 0, 0, ',', '.'),
                ], $data['by_category'] ?? []);
                $summary = $data['summary'] ?? [];
                $rows[] = [
                    'category' => 'TOTAL',
                    'quantity' => $summary['total_quantity'] ?? 0,
                    'value' => number_format($summary['total_value'] ?? 0, 0, ',', '.'),
                ];
                return [$headers, $rows];
            },
            'aging' => function () use ($data) {
                $headers = ['SKU', 'Produk', 'Gudang', 'Total Qty', 'Terakhir Bergerak', 'Hari Diam'];
                $rows = array_map(fn($item) => [
                    'sku' => $item['sku'] ?? '',
                    'name' => $item['name'] ?? '',
                    'warehouse' => $item['warehouse_code'] ?? '',
                    'quantity' => $item['total_qty'] ?? 0,
                    'last_movement' => $item['last_movement'] ?? '-',
                    'days' => round($item['days_since_movement'] ?? 0) . ' hari',
                ], $data);
                return [$headers, $rows];
            },
            'expiry' => function () use ($data) {
                $headers = ['SKU', 'Produk', 'Gudang', 'Kuantitas', 'Tgl Kadaluarsa', 'Sisa Hari'];
                $rows = array_map(fn($item) => [
                    'sku' => $item['product']['sku'] ?? '',
                    'name' => $item['product']['name'] ?? '',
                    'warehouse' => $item['warehouse']['name'] ?? '',
                    'quantity' => $item['quantity'] ?? 0,
                    'expiry_date' => $item['expiry_date'] ?? '-',
                    'remaining' => $item['expiry_date'] ? max(0, ceil((strtotime($item['expiry_date']) - time()) / 86400)) . ' hari' : '-',
                ], $data);
                return [$headers, $rows];
            },
            'activity' => function () use ($data) {
                $headers = ['Tanggal', 'SKU', 'Produk', 'Gudang', 'Aktivitas', 'Oleh'];
                $rows = array_map(fn($item) => [
                    'date' => $item['created_at'] ?? '',
                    'sku' => $item['product']['sku'] ?? '',
                    'product' => $item['product']['name'] ?? '',
                    'warehouse' => $item['warehouse']['name'] ?? '',
                    'type' => strtoupper($item['transaction_type'] ?? $item['type'] ?? ''),
                    'user' => $item['creator']['name'] ?? $item['user']['name'] ?? 'Sistem',
                ], $data);
                return [$headers, $rows];
            },
            'utilization' => function () use ($data) {
                $headers = ['Nama Gudang', 'Kode', 'Zona', 'Rak', 'Total Slot', 'Slot Terisi', 'Persentase'];
                $rows = array_map(fn($item) => [
                    'name' => $item['warehouse_name'] ?? '',
                    'code' => $item['warehouse_code'] ?? '',
                    'zones' => $item['total_zones'] ?? 0,
                    'racks' => $item['total_racks'] ?? 0,
                    'slots' => $item['total_slots'] ?? 0,
                    'filled' => $item['utilization'] ?? 0,
                    'pct' => ($item['total_slots'] ?? 0) > 0 ? round(($item['utilization'] ?? 0) / ($item['total_slots'] ?? 1) * 100) . '%' : '0%',
                ], $data);
                return [$headers, $rows];
            },
        ];

        return ($formatters[$type] ?? $formatters['stock'])();
    }
}
