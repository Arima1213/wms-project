<?php

namespace App\Services\Warehouse;

use App\Models\Zone;
use Illuminate\Pagination\LengthAwarePaginator;

class ZoneService
{
    public function list(int $warehouseId, int $perPage = 50): LengthAwarePaginator
    {
        return Zone::where('warehouse_id', $warehouseId)
            ->withCount('racks')
            ->orderBy('sort_order')
            ->paginate($perPage);
    }

    public function show(int $warehouseId, int $zoneId): Zone
    {
        return Zone::where('warehouse_id', $warehouseId)
            ->where('id', $zoneId)
            ->with('racks')
            ->firstOrFail();
    }

    public function create(int $warehouseId, array $data): Zone
    {
        $data['warehouse_id'] = $warehouseId;
        $data['is_active'] = true;
        $data['color'] = $data['color'] ?? '#3B82F6';
        $data['zone_type'] = $data['zone_type'] ?? 'fast_moving';
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return Zone::create($data);
    }

    public function update(int $warehouseId, int $zoneId, array $data): Zone
    {
        $zone = $this->show($warehouseId, $zoneId);
        $zone->update($data);
        return $zone->fresh();
    }

    public function delete(int $warehouseId, int $zoneId): void
    {
        $this->show($warehouseId, $zoneId)->delete();
    }

    public function activate(int $zoneId): Zone
    {
        $zone = Zone::findOrFail($zoneId);
        $zone->update(['is_active' => true]);
        return $zone;
    }

    public function deactivate(int $zoneId): Zone
    {
        $zone = Zone::findOrFail($zoneId);
        $zone->update(['is_active' => false]);
        return $zone;
    }
}
