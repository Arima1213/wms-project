<?php

namespace App\Services;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class WarehouseService
{
    public function list(array $filters): Collection
    {
        $query = Warehouse::query();
        return $query->get();
    }

    public function create(array $data): Warehouse
    {
        return Warehouse::create($data);
    }

    public function update(int $id, array $data): Warehouse
    {
        $item = Warehouse::findOrFail($id);
        $item->update($data);
        return $item->fresh();
    }

    public function delete(int $id): void
    {
        Warehouse::findOrFail($id)->delete();
    }
}
