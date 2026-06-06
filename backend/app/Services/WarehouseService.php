<?php

namespace App\Services;

use App\Models\Warehouse;
use App\Models\Planogram;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class WarehouseService
{
    public function list(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $query = Warehouse::query();

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(fn(Builder $q) => $q
                ->where('name', 'ilike', "%{$search}%")
                ->orWhere('code', 'ilike', "%{$search}%")
            );
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function show(int $id): Warehouse
    {
        return Warehouse::with([
            'zones' => fn($q) => $q->withCount('racks')->orderBy('sort_order'),
            'zones.racks' => fn($q) => $q->withCount('levels')->orderBy('code'),
            'zones.racks.levels.slots',
            'planogram' => fn($q) => $q->latest()->take(1),
        ])->findOrFail($id);
    }

    public function create(array $data): Warehouse
    {
        $warehouse = Warehouse::create(array_merge($data, ['is_active' => true]));

        // Automatically initialize an empty planogram for the new warehouse
        Planogram::create([
            'warehouse_id' => $warehouse->id,
            'canvas_width' => 5000,
            'canvas_height' => 3000,
            'grid_size' => 50,
            'version' => '1.0',
            'canvas_data' => ['zones' => [], 'items' => []],
        ]);

        return $warehouse;
    }

    public function update(int $id, array $data): Warehouse
    {
        $warehouse = Warehouse::findOrFail($id);
        $warehouse->update($data);
        return $warehouse->fresh();
    }

    public function delete(int $id): void
    {
        Warehouse::findOrFail($id)->delete();
    }

    public function getSummary(int $id): array
    {
        $warehouse = Warehouse::with(['zones.racks', 'planogram'])->findOrFail($id);
        $zoneCount = $warehouse->zones->count();
        $rackCount = $warehouse->zones->flatMap->racks->count();

        return [
            'warehouse_id' => $warehouse->id,
            'zone_count' => $zoneCount,
            'rack_count' => $rackCount,
            'planogram_version' => $warehouse->planogram->version ?? null,
        ];
    }

    public function getUtilization(int $id): array
    {
        $warehouse = Warehouse::with(['zones.racks.levels.slots.currentStocks'])->findOrFail($id);
        $totalSlots = 0;
        $filledSlots = 0;

        foreach ($warehouse->zones as $zone) {
            foreach ($zone->racks as $rack) {
                foreach ($rack->levels as $level) {
                    foreach ($level->slots as $slot) {
                        $totalSlots++;
                        // If it has fixed product or current stock, consider it filled
                        if ($slot->fixed_product_id || $slot->currentStocks->count() > 0) {
                            $filledSlots++;
                        }
                    }
                }
            }
        }

        return [
            'warehouse_id' => $warehouse->id,
            'total_slots' => $totalSlots,
            'filled_slots' => $filledSlots,
            'empty_slots' => $totalSlots - $filledSlots,
            'utilization_percent' => $totalSlots > 0 ? round(($filledSlots / $totalSlots) * 100, 1) : 0,
        ];
    }
}
