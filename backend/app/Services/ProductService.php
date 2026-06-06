<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function list(array $filters): Collection
    {
        $query = Product::query();
        return $query->get();
    }

    public function create(array $data): Product
    {
        return Product::create($data);
    }

    public function update(int $id, array $data): Product
    {
        $item = Product::findOrFail($id);
        $item->update($data);
        return $item->fresh();
    }

    public function delete(int $id): void
    {
        Product::findOrFail($id)->delete();
    }
}
