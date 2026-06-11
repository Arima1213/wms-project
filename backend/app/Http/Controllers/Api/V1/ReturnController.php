<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Returns;
use App\Http\Requests\StoreReturnRequest;
use App\Http\Requests\UpdateReturnRequest;
use App\Http\Resources\ReturnResource;
use App\Services\ReturnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReturnController extends Controller
{
    protected ReturnService $returnService;

    public function __construct(ReturnService $returnService)
    {
        $this->returnService = $returnService;
    }

    public function index(Request $request)
    {
        $query = Returns::with(['warehouse', 'customer', 'supplier', 'creator', 'items.product']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        $returns = $query->orderByDesc('created_at')->paginate($request->get('per_page', 25));
        return ReturnResource::collection($returns);
    }

    public function show(string|int $return): ReturnResource
    {
        $return = Returns::with(['warehouse', 'customer', 'supplier', 'creator', 'processor', 'items.product'])
            ->findOrFail($return);
        return new ReturnResource($return);
    }

    public function store(StoreReturnRequest $request): JsonResponse
    {
        $return = $this->returnService->create(
            $request->validated(),
            Auth::id()
        );

        return response()->json([
            'message' => 'Return berhasil dibuat',
            'data' => new ReturnResource($return),
        ], 201);
    }

    public function update(UpdateReturnRequest $request, Returns $return): JsonResponse
    {
        if (!in_array($return->status, ['draft', 'pending'])) {
            return response()->json(['message' => 'Return cannot be updated in its current state.'], 422);
        }

        $data = $request->validated();

        if (isset($data['items'])) {
            $return->items()->delete();
            $totalRefund = 0;
            foreach ($data['items'] as $item) {
                $lineRefund = ($item['refund_amount'] ?? 0);
                \App\Models\ReturnItem::create([
                    'return_id' => $return->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'condition' => $item['condition'] ?? 'good',
                    'resolution' => $item['resolution'] ?? 'restock',
                    'refund_amount' => $lineRefund,
                    'notes' => $item['notes'] ?? null,
                ]);
                $totalRefund += $lineRefund;
            }
            $data['refund_amount'] = $totalRefund;
            unset($data['items']);
        }

        $return->update($data);

        return response()->json([
            'message' => 'Return berhasil diperbarui',
            'data' => new ReturnResource($return->fresh(['items.product', 'warehouse', 'customer', 'supplier'])),
        ]);
    }

    public function approve(Returns $return): JsonResponse
    {
        $return = $this->returnService->approve($return, Auth::id());
        return response()->json([
            'message' => 'Return berhasil disetujui',
            'data' => new ReturnResource($return),
        ]);
    }

    public function process(Returns $return): JsonResponse
    {
        $return = $this->returnService->process($return, Auth::id());
        return response()->json([
            'message' => 'Return berhasil diproses — stok dikembalikan',
            'data' => new ReturnResource($return),
        ]);
    }

    public function reject(Request $request, Returns $return): JsonResponse
    {
        $request->validate(['reason' => 'nullable|string|max:500']);
        $return = $this->returnService->reject($return, Auth::id(), $request->reason);
        return response()->json([
            'message' => 'Return ditolak',
            'data' => new ReturnResource($return),
        ]);
    }

    public function cancel(Returns $return): JsonResponse
    {
        $return = $this->returnService->cancel($return, Auth::id());
        return response()->json([
            'message' => 'Return dibatalkan',
            'data' => new ReturnResource($return),
        ]);
    }

    public function destroy(Returns $return): JsonResponse
    {
        if ($return->status !== 'draft') {
            return response()->json(['message' => 'Only draft returns can be deleted.'], 422);
        }
        $return->items()->delete();
        $return->delete();
        return response()->json(['message' => 'Return berhasil dihapus']);
    }
}
