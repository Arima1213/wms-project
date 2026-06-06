<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use App\Http\Resources\WarehouseResource;
use App\Services\WarehouseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function __construct(private WarehouseService $warehouseService)
    {
    }

    public function index(Request $request)
    {
        $warehouses = $this->warehouseService->list(
            $request->only(['search', 'is_active']),
            $request->integer('per_page', 25)
        );

        return WarehouseResource::collection($warehouses);
    }

    public function show(string|int $warehouse): WarehouseResource
    {
        return new WarehouseResource($this->warehouseService->show((int) $warehouse));
    }

    public function store(StoreWarehouseRequest $request): JsonResponse
    {
        $warehouse = $this->warehouseService->create($request->validated());
        return response()->json(['data' => new WarehouseResource($warehouse)], 201);
    }

    public function update(UpdateWarehouseRequest $request, string|int $warehouse): JsonResponse
    {
        $updated = $this->warehouseService->update((int) $warehouse, $request->validated());
        return response()->json(['data' => new WarehouseResource($updated)]);
    }

    public function destroy(string|int $warehouse): JsonResponse
    {
        $this->authorize('warehouse.delete');
        $this->warehouseService->delete((int) $warehouse);
        return response()->json(['message' => 'Warehouse deleted']);
    }

    public function summary(string|int $warehouse): JsonResponse
    {
        $summary = $this->warehouseService->getSummary((int) $warehouse);
        return response()->json(['data' => $summary]);
    }

    public function utilization(string|int $warehouse): JsonResponse
    {
        $utilization = $this->warehouseService->getUtilization((int) $warehouse);
        return response()->json(['data' => $utilization]);
    }
}