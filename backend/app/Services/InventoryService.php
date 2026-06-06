<?php

namespace App\Services;

use App\Models\Inventory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function list(array $filters): Collection
    {
        $query = Inventory::query();
        return $query->get();
    }

    public function create(array $data): Inventory
    {
        return Inventory::create($data);
    }

    public function update(int $id, array $data): Inventory
    {
        $item = Inventory::findOrFail($id);
        $item->update($data);
        return $item->fresh();
    }

    public function delete(int $id): void
    {
        Inventory::findOrFail($id)->delete();
    }
}
