<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Returns as ReturnModel;
use App\Http\Resources\ReturnResource;
use App\Http\Requests\StoreReturnRequest;
use App\Http\Requests\UpdateReturnRequest;
use App\Services\ReturnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReturnController extends Controller
{
    protected ReturnService $returnService;

    public function __construct(ReturnService $returnService)
    {
        $this->returnService = $returnService;
    }

    public function index(Request $request)
    {
        $query = ReturnModel::with(['warehouse', 'customer', 'supplier', 'creator', 'items.product']);

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

    public function show(string|int $id): ReturnResource
    {
        $return = ReturnModel::with(['warehouse', 'customer', 'supplier', 'creator', 'items.product'])
            ->findOrFail($id);
        return new ReturnResource($return);
    }

    public function store(StoreReturnRequest $request): JsonResponse
    {
        $return = $this->returnService->create($request->validated(), $request->user()->id);
        return response()->json(new ReturnResource($return), 201);
    }

    public function update(UpdateReturnRequest $request, string|int $id): JsonResponse
    {
        $return = ReturnModel::findOrFail($id);

        if (!in_array($return->status, ['draft', 'pending'])) {
            return response()->json(['message' => 'Cannot modify non-draft/pending return.'], 422);
        }

        $return->update($request->validated());
        return response()->json(new ReturnResource($return->fresh()->loadMissing(['items.product', 'warehouse'])));
    }

    public function destroy(string|int $id): JsonResponse
    {
        $return = ReturnModel::findOrFail($id);
        if (!in_array($return->status, ['draft', 'cancelled'])) {
            return response()->json(['message' => 'Only draft/cancelled returns can be deleted.'], 422);
        }
        $return->delete();
        return response()->json(null, 204);
    }

    public function submit(string|int $id, Request $request): JsonResponse
    {
        $return = ReturnModel::findOrFail($id);
        if ($return->status !== 'draft') {
            return response()->json(['message' => 'Only draft returns can be submitted.'], 422);
        }
        $return->update(['status' => 'pending']);
        return response()->json(new ReturnResource($return->fresh()->loadMissing(['items.product', 'warehouse'])));
    }

    public function approve(string|int $id, Request $request): JsonResponse
    {
        $return = ReturnModel::findOrFail($id);
        $return = $this->returnService->approve($return, $request->user()->id);
        return response()->json(new ReturnResource($return));
    }

    public function process(string|int $id, Request $request): JsonResponse
    {
        $return = ReturnModel::findOrFail($id);
        $return = $this->returnService->process($return, $request->user()->id);
        return response()->json(new ReturnResource($return));
    }

    public function reject(string|int $id, Request $request): JsonResponse
    {
        $return = ReturnModel::findOrFail($id);
        $return = $this->returnService->reject($return, $request->user()->id, $request->reason);
        return response()->json(new ReturnResource($return));
    }

    public function cancel(string|int $id, Request $request): JsonResponse
    {
        $return = ReturnModel::findOrFail($id);
        $return = $this->returnService->cancel($return, $request->user()->id);
        return response()->json(new ReturnResource($return));
    }
}
