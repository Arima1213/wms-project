<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Transfer;
use App\Http\Requests\StoreTransferRequest;
use App\Services\DocumentSequenceService;
use App\Services\TransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransferController extends Controller
{
    protected TransferService $transferService;
    protected DocumentSequenceService $documentSequence;

    public function __construct(TransferService $transferService, DocumentSequenceService $documentSequence)
    {
        $this->transferService = $transferService;
        $this->documentSequence = $documentSequence;
    }

    public function index(Request $request): JsonResponse
    {
        $query = Transfer::with('sourceWarehouse', 'destWarehouse', 'user');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $transfers = $query->orderByDesc('created_at')->paginate($request->get('per_page', 25));
        return response()->json($transfers);
    }

    public function show(string|int $transfer): JsonResponse
    {
        $transfer = Transfer::with(['sourceWarehouse', 'destWarehouse', 'user', 'items.product'])->findOrFail($transfer);
        return response()->json(['data' => $transfer]);
    }

    public function store(StoreTransferRequest $request): JsonResponse
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $transfer = Transfer::create([
                'transfer_number' => $this->documentSequence->getNextNumber('TRF'),
                'source_warehouse_id' => $validated['source_warehouse_id'],
                'dest_warehouse_id' => $validated['dest_warehouse_id'],
                'created_by' => $request->user()->id,
                'status' => 'pending',
                'reason' => $validated['reason'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $transfer->items()->create($item);
            }

            DB::commit();
            return response()->json(['data' => $transfer->load('items')], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create transfer', 'error' => $e->getMessage()], 500);
        }
    }

    public function approve(Request $request, string|int $transfer): JsonResponse
    {
        $transfer = Transfer::findOrFail($transfer);
        $this->authorize('approve', $transfer);

        if ($transfer->status !== 'pending') {
            return response()->json(['message' => 'Cannot approve this transfer'], 422);
        }
        $transfer->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $request->user()->id,
        ]);
        return response()->json(['data' => $transfer]);
    }

    public function reject(Request $request, string|int $transfer): JsonResponse
    {
        $transfer = Transfer::findOrFail($transfer);
        $this->authorize('reject', $transfer);

        if ($transfer->status !== 'pending') {
            return response()->json(['message' => 'Cannot reject this transfer'], 422);
        }
        $transfer->update(['status' => 'rejected']);
        return response()->json(['data' => $transfer]);
    }

    public function execute(Request $request, string|int $transferId): JsonResponse
    {
        $transfer = Transfer::findOrFail($transferId);
        $this->authorize('execute', $transfer);
        
        try {
            $executedTransfer = $this->transferService->execute($transfer, $request->user()->id);
            return response()->json(['data' => $executedTransfer]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}