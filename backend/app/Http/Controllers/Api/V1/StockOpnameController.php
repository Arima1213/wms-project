<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\StockOpname;
use App\Services\StockOpnameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockOpnameController extends Controller
{
    protected StockOpnameService $stockOpnameService;

    public function __construct(StockOpnameService $stockOpnameService)
    {
        $this->stockOpnameService = $stockOpnameService;
    }

    public function index(Request $request): JsonResponse
    {
        $query = StockOpname::with('warehouse', 'user');
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        $opnames = $query->orderByDesc('created_at')->paginate($request->get('per_page', 25));
        return response()->json($opnames);
    }

    public function show(string|int $opname): JsonResponse
    {
        $opname = StockOpname::with(['warehouse', 'user', 'items.product'])->findOrFail($opname);
        return response()->json(['data' => $opname]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', StockOpname::class);

        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'opname_number' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $opname = StockOpname::create([
            'opname_number' => $validated['opname_number'] ?? 'SO-' . date('Ymd') . '-' . str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT),
            'warehouse_id' => $validated['warehouse_id'],
            'created_by' => $request->user()->id,
            'status' => 'draft',
            'type' => $request->type ?? 'full',
            'start_date' => $request->start_date ?? now()->toDateString(),
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json(['data' => $opname], 201);
    }

    public function start(Request $request, string|int $opname): JsonResponse
    {
        $opname = StockOpname::findOrFail($opname);
        $this->authorize('start', $opname);

        if ($opname->status !== 'draft') {
            return response()->json(['message' => 'Cannot start this stock opname'], 422);
        }
        $opname->update(['status' => 'in_progress']);
        return response()->json(['data' => $opname]);
    }

    public function update(Request $request, string|int $opnameId): JsonResponse
    {
        $opname = StockOpname::findOrFail($opnameId);
        $this->authorize('update', $opname);
        
        $validated = $request->validate([
            'notes' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.system_qty' => 'required|numeric',
            'items.*.actual_qty' => 'required|numeric',
            'items.*.difference_qty' => 'required|numeric',
            'items.*.slot_id' => 'nullable|exists:rack_slots,id',
        ]);

        DB::beginTransaction();
        try {
            if (isset($validated['notes'])) {
                $opname->update(['notes' => $validated['notes']]);
            }

            if (isset($validated['items'])) {
                // Clear existing items and recreate
                $opname->items()->delete();
                foreach ($validated['items'] as $item) {
                    $opname->items()->create([
                        'product_id' => $item['product_id'],
                        'slot_id' => $item['slot_id'] ?? null,
                        'system_qty' => $item['system_qty'],
                        'counted_qty' => $item['actual_qty'],
                        'variance' => $item['difference_qty'],
                        'variance_status' => $item['difference_qty'] == 0 ? 'match' : ($item['difference_qty'] > 0 ? 'over' : 'short'),
                        'counted_by' => $request->user()->id,
                        'counted_at' => now(),
                    ]);
                }
            }
            DB::commit();
            return response()->json(['data' => $opname->load('items')]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update stock opname', 'error' => $e->getMessage()], 500);
        }
    }

    public function submit(Request $request, string|int $opname): JsonResponse
    {
        $opname = StockOpname::findOrFail($opname);
        $this->authorize('submit', $opname);

        if ($opname->status !== 'in_progress') {
            return response()->json(['message' => 'Cannot submit this stock opname'], 422);
        }
        $opname->update(['status' => 'submitted', 'submitted_at' => now()]);
        return response()->json(['data' => $opname]);
    }

    public function approve(Request $request, string|int $opnameId): JsonResponse
    {
        $opname = StockOpname::findOrFail($opnameId);
        $this->authorize('approve', $opname);
        
        try {
            $approvedOpname = $this->stockOpnameService->approve($opname, $request->user()->id);
            return response()->json(['data' => $approvedOpname]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}