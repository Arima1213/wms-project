<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\StockOpname;
use App\Http\Resources\StockOpnameResource;
use App\Services\DocumentSequenceService;
use App\Services\StockOpnameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockOpnameController extends Controller
{
    protected StockOpnameService $stockOpnameService;
    protected DocumentSequenceService $documentSequence;

    public function __construct(StockOpnameService $stockOpnameService, DocumentSequenceService $documentSequence)
    {
        $this->stockOpnameService = $stockOpnameService;
        $this->documentSequence = $documentSequence;
    }

    public function index(Request $request)
    {
        $query = StockOpname::with(['warehouse', 'user', 'zone']);
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        $opnames = $query->orderByDesc('created_at')->paginate($request->get('per_page', 25));
        return StockOpnameResource::collection($opnames);
    }

    public function show(string|int $opname): StockOpnameResource
    {
        $opname = StockOpname::with(['warehouse', 'user', 'zone', 'items.product', 'items.slot'])->findOrFail($opname);
        return new StockOpnameResource($opname);
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
            'opname_number' => $validated['opname_number'] ?? $this->documentSequence->getNextNumber('SO'),
            'warehouse_id' => $validated['warehouse_id'],
            'created_by' => $request->user()->id,
            'status' => 'draft',
            'type' => $request->type ?? 'full',
            'start_date' => $request->start_date ?? now()->toDateString(),
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json(['data' => new StockOpnameResource($opname->load('warehouse', 'user'))], 201);
    }

    public function start(Request $request, string|int $opname): JsonResponse
    {
        $opname = StockOpname::findOrFail($opname);
        $this->authorize('start', $opname);

        if ($opname->status !== 'draft') {
            return response()->json(['message' => 'Cannot start this stock opname'], 422);
        }
        $opname->update(['status' => 'in_progress']);
        return response()->json(['data' => new StockOpnameResource($opname->load('warehouse', 'user'))]);
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
                // Upsert items using product_id + slot_id as composite key to prevent data loss on crash
                $existingItemIds = collect($validated['items'])
                    ->map(function ($itemData) use ($opname, $request) {
                        $existing = $opname->items()
                            ->where('product_id', $itemData['product_id'])
                            ->where('slot_id', $itemData['slot_id'] ?? null)
                            ->first();

                        $data = [
                            'product_id' => $itemData['product_id'],
                            'slot_id' => $itemData['slot_id'] ?? null,
                            'system_qty' => $itemData['system_qty'],
                            'counted_qty' => $itemData['actual_qty'],
                            'variance' => $itemData['difference_qty'],
                            'variance_status' => $itemData['difference_qty'] == 0 ? 'match' : ($itemData['difference_qty'] > 0 ? 'over' : 'short'),
                            'counted_by' => $request->user()->id,
                            'counted_at' => now(),
                        ];

                        if ($existing) {
                            $existing->update($data);
                            return $existing->id;
                        }

                        $newItem = $opname->items()->create($data);
                        return $newItem->id;
                    });

                // Soft-delete items that are no longer in the updated list
                $opname->items()->whereNotIn('id', $existingItemIds)->delete();
            }
            DB::commit();
            return response()->json(['data' => new StockOpnameResource($opname->load('items.product', 'warehouse', 'user'))]);
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
        return response()->json(['data' => new StockOpnameResource($opname->load('warehouse', 'user'))]);
    }

    public function approve(Request $request, string|int $opnameId): JsonResponse
    {
        $opname = StockOpname::findOrFail($opnameId);
        $this->authorize('approve', $opname);
        
        try {
            $approvedOpname = $this->stockOpnameService->approve($opname, $request->user()->id);
            return response()->json(['data' => new StockOpnameResource($approvedOpname->load(['warehouse', 'user', 'zone', 'items.product']))]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function cancel(Request $request, string|int $opname): JsonResponse
    {
        $opname = StockOpname::findOrFail($opname);
        $this->authorize('cancel', $opname);

        if (in_array($opname->status, ['approved', 'cancelled'])) {
            return response()->json(['message' => 'Cannot cancel this stock opname'], 422);
        }
        $opname->update(['status' => 'cancelled']);
        return response()->json(['data' => new StockOpnameResource($opname->load('warehouse', 'user'))]);
    }
}
