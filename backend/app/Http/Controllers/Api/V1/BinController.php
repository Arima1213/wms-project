<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Bin;
use App\Http\Resources\BinResource;
use App\Http\Requests\StoreBinRequest;
use App\Http\Requests\UpdateBinRequest;
use App\Services\BinService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BinController extends Controller
{
    protected BinService $binService;

    public function __construct(BinService $binService)
    {
        $this->binService = $binService;
    }

    public function index(Request $request)
    {
        $bins = $this->binService->list($request->only(
            'rack_id', 'warehouse_id', 'bin_type', 'is_active', 'search', 'per_page'
        ));
        return BinResource::collection($bins);
    }

    public function store(StoreBinRequest $request): JsonResponse
    {
        $bin = $this->binService->create($request->validated());
        return response()->json(new BinResource($bin->load(['rack.zone.warehouse'])), 201);
    }

    public function show(Bin $bin): BinResource
    {
        return new BinResource($bin->load(['rack.zone.warehouse', 'stocks.product']));
    }

    public function update(UpdateBinRequest $request, Bin $bin): JsonResponse
    {
        $bin = $this->binService->update($bin, $request->validated());
        return response()->json(new BinResource($bin));
    }

    public function destroy(Bin $bin): JsonResponse
    {
        if ($bin->stocks()->count() > 0) {
            return response()->json(['message' => 'Cannot delete bin with existing stock.'], 422);
        }
        $bin->delete();
        return response()->json(null, 204);
    }

    public function toggleActive(Bin $bin): JsonResponse
    {
        $bin = $this->binService->toggleActive($bin);
        return response()->json(new BinResource($bin));
    }

    public function occupancy(Bin $bin): JsonResponse
    {
        $occupancy = $this->binService->getOccupancy($bin->load('stocks.product'));
        return response()->json($occupancy);
    }
}
