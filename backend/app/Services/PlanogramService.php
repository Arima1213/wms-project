<?php

namespace App\Services;

use App\Models\Planogram;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PlanogramService
{
    public function list(array $filters): Collection
    {
        $query = Planogram::query();
        return $query->get();
    }

    public function create(array $data): Planogram
    {
        return Planogram::create($data);
    }

    public function update(int $id, array $data): Planogram
    {
        $item = Planogram::findOrFail($id);
        $item->update($data);
        return $item->fresh();
    }

    public function delete(int $id): void
    {
        Planogram::findOrFail($id)->delete();
    }
}
