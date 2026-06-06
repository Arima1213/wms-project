<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreZoneRequest;
use App\Http\Requests\UpdateZoneRequest;
use App\Http\Resources\ZoneResource;
use App\Services\Warehouse\ZoneService;
use Illuminate\Http\JsonResponse;

class ZoneController extends Controller
{
    public function __construct(private ZoneService $zoneService)
    {
    }

    public function index(string|int $warehouse)
    {
        $zones = $this->zoneService->list((int) $warehouse);
        return ZoneResource::collection($zones);
    }

    public function store(StoreZoneRequest $request, string|int $warehouse): JsonResponse
    {
        $zone = $this->zoneService->create((int) $warehouse, $request->validated());
        return response()->json(['data' => new ZoneResource($zone)], 201);
    }

    public function show(string|int $warehouse, string|int $zone): ZoneResource
    {
        return new ZoneResource($this->zoneService->show((int) $warehouse, (int) $zone));
    }

    public function update(UpdateZoneRequest $request, string|int $warehouse, string|int $zone): JsonResponse
    {
        $updated = $this->zoneService->update((int) $warehouse, (int) $zone, $request->validated());
        return response()->json(['data' => new ZoneResource($updated)]);
    }

    public function destroy(string|int $warehouse, string|int $zone): JsonResponse
    {
        $this->authorize('zone.delete');
        $this->zoneService->delete((int) $warehouse, (int) $zone);
        return response()->json(['message' => 'Zone deleted']);
    }

    public function activate(string|int $zone): JsonResponse
    {
        $this->authorize('zone.update');
        $updated = $this->zoneService->activate((int) $zone);
        return response()->json(['data' => new ZoneResource($updated)]);
    }

    public function deactivate(string|int $zone): JsonResponse
    {
        $this->authorize('zone.update');
        $updated = $this->zoneService->deactivate((int) $zone);
        return response()->json(['data' => new ZoneResource($updated)]);
    }
}