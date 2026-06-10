<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\InventoryResource;
use App\Models\{Inventory, StockTransaction, Product, Warehouse};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
    public function stock(Request $request): JsonResponse
    {
        $query = Inventory::with(['product:id,sku,name,category_id', 'product.category:id,name', 'warehouse:id,code,name'])
            ->when($request->warehouse_id, fn($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->category_id, fn($q) => $q->whereHas('product', fn($pq) => $pq->where('category_id', $request->category_id)));

        $data = $query->paginate($request->get('per_page', 50));
        return InventoryResource::collection($data);
    }

    public function mutations(Request $request): JsonResponse
    {
        $query = StockTransaction::with(['product:id,sku,name', 'warehouse:id,code,name', 'creator:id,name'])
            ->when($request->warehouse_id, fn($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->product_id, fn($q) => $q->where('product_id', $request->product_id))
            ->when($request->type, fn($q) => $q->where('transaction_type', $request->type))
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
            ->leftJoin('stock_transactions as it', fn($j) => $j->on('i.product_id', '=', 'it.product_id')->on('i.warehouse_id', '=', 'it.warehouse_id'))
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

        return InventoryResource::collection($data);
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
            'total_slots' => $w->zones->flatMap(fn($z) => $z->racks->flatMap(fn($r) => $r->levels->flatMap(fn($l) => $l->slots)))->count(),
            'utilization' => $w->zones->flatMap(fn($z) => $z->racks->flatMap(fn($r) => $r->levels->flatMap(fn($l) => $l->slots)))
                ->filter(fn($s) => $s->status !== 'empty')->count(),
        ]);

        return response()->json(['data' => $result]);
    }

    public function activity(Request $request): JsonResponse
    {
        $data = StockTransaction::with(['creator:id,name', 'warehouse:id,code,name', 'product:id,sku,name'])
            ->when($request->warehouse_id, fn($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->orderByDesc('created_at')
            ->paginate($request->get('per_page', 50));

        return response()->json($data);
    }

    public function valuation(Request $request): JsonResponse
    {
        $warehouseId = $request->warehouse_id;

        $valuation = DB::table('inventory as i')
            ->join('products as p', 'i.product_id', '=', 'p.id')
            ->select(
                DB::raw('SUM(i.quantity) as total_quantity'),
                DB::raw('SUM(i.quantity * COALESCE(i.unit_cost, 0)) as total_value'),
                DB::raw('COUNT(DISTINCT p.id) as total_products')
            )
            ->when($warehouseId, fn($q) => $q->where('i.warehouse_id', $warehouseId))
            ->where('i.quantity', '>', 0)
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
            ->groupBy('c.name')
            ->get();

        return response()->json([
            'data' => [
                'total_quantity' => $valuation->total_quantity ?? 0,
                'total_value' => $valuation->total_value ?? 0,
                'total_products' => $valuation->total_products ?? 0,
                'by_category' => $byCategory
            ]
        ]);
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
        $query = Inventory::with(['product:id,sku,name,category_id', 'product.category:id,name', 'warehouse:id,code,name'])
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId));
        return $query->get()->toArray();
    }

    private function getMutationData($warehouseId, array $params): array
    {
        $query = StockTransaction::with(['product:id,sku,name', 'warehouse:id,code,name', 'creator:id,name'])
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->when($params['type'] ?? null, fn($q) => $q->where('transaction_type', $params['type']))
            ->when($params['from'] ?? null, fn($q) => $q->whereDate('created_at', '>=', $params['from']))
            ->when($params['to'] ?? null, fn($q) => $q->whereDate('created_at', '<=', $params['to']))
            ->orderByDesc('created_at')
            ->limit(5000);
        return $query->get()->toArray();
    }

    private function getValuationData($warehouseId): array
    {
        $val = DB::table('inventory as i')
            ->join('products as p', 'i.product_id', '=', 'p.id')
            ->select(
                DB::raw('SUM(i.quantity) as total_quantity'),
                DB::raw('SUM(i.quantity * COALESCE(i.unit_cost, 0)) as total_value'),
                DB::raw('COUNT(DISTINCT p.id) as total_products')
            )
            ->when($warehouseId, fn($q) => $q->where('i.warehouse_id', $warehouseId))
            ->where('i.quantity', '>', 0)
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
    }

    private function getAgingData($warehouseId, array $params): array
    {
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
            ->groupBy('p.id', 'p.sku', 'p.name', 'w.code')
            ->having(DB::raw('MAX(it.created_at)'), '<', $cutoff)
            ->orderByDesc('days_since_movement')
            ->get()
            ->toArray();
    }

    private function getExpiryData($warehouseId, array $params): array
    {
        $within = (int) ($params['within_days'] ?? 30);
        return Inventory::with(['product:id,sku,name', 'warehouse:id,code,name'])
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now(), now()->addDays($within)])
            ->orderBy('expiry_date')
            ->get()
            ->toArray();
    }

    private function getActivityData($warehouseId, array $params): array
    {
        return StockTransaction::with(['creator:id,name', 'warehouse:id,code,name', 'product:id,sku,name'])
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->orderByDesc('created_at')
            ->limit(5000)
            ->get()
            ->toArray();
    }

    private function getUtilizationData($warehouseId): array
    {
        $warehouseQuery = Warehouse::query()->when($warehouseId, fn($q) => $q->where('id', $warehouseId));
        $warehouses = $warehouseQuery->with(['zones.racks.levels.slots'])->get();

        return $warehouses->map(fn($w) => [
            'warehouse_name' => $w->name,
            'warehouse_code' => $w->code,
            'total_zones' => $w->zones->count(),
            'total_racks' => $w->zones->flatMap(fn($z) => $z->racks)->count(),
            'total_slots' => $w->zones->flatMap(fn($z) => $z->racks->flatMap(fn($r) => $r->levels->flatMap(fn($l) => $l->slots)))->count(),
            'utilization' => $w->zones->flatMap(fn($z) => $z->racks->flatMap(fn($r) => $r->levels->flatMap(fn($l) => $l->slots)))
                ->filter(fn($s) => $s->status !== 'empty')->count(),
        ])->toArray();
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
