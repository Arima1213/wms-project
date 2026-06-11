<?php

namespace App\Services;

use App\Models\Bin;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class BinService
{
    public function list(array $filters = [])
    {
        $query = Bin::with(['rack.zone.warehouse']);

        if (!empty($filters['rack_id'])) {
            $query->where('rack_id', $filters['rack_id']);
        }
        if (!empty($filters['warehouse_id'])) {
            $query->whereHas('rack.zone', fn($q) => $q->where('warehouse_id', $filters['warehouse_id']));
        }
        if (!empty($filters['bin_type'])) {
            $query->where('bin_type', $filters['bin_type']);
        }
        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('code', 'like', "%{$filters['search']}%");
            });
        }

        return $query->orderBy('rack_id')->orderBy('position')->paginate($filters['per_page'] ?? 25);
    }

    public function create(array $data): Bin
    {
        return DB::transaction(function () use ($data) {
            $data['code'] = $data['code'] ?? $this->generateCode($data['rack_id']);
            return Bin::create($data);
        });
    }

    public function update(Bin $bin, array $data): Bin
    {
        $bin->update($data);
        return $bin->fresh()->load(['rack.zone.warehouse']);
    }

    public function toggleActive(Bin $bin): Bin
    {
        $bin->update(['is_active' => !$bin->is_active]);
        return $bin->fresh();
    }

    public function generateCode(int $rackId): string
    {
        $max = Bin::where('rack_id', $rackId)->max('position') ?? 0;
        return 'BIN-' . str_pad($rackId, 4, '0', STR_PAD_LEFT) . '-' . str_pad($max + 1, 3, '0', STR_PAD_LEFT);
    }

    public function getOccupancy(Bin $bin): array
    {
        $stocks = $bin->stocks;
        $totalQty = $stocks->sum('quantity');
        $skuCount = $stocks->count();

        $weightPct = $bin->max_weight ? min(100, ($stocks->sum(fn($s) => $s->quantity * ($s->product?->weight ?? 0)) / $bin->max_weight) * 100) : 0;

        return [
            'total_items' => $totalQty,
            'sku_count' => $skuCount,
            'weight_utilization_pct' => round($weightPct, 1),
        ];
    }
}
