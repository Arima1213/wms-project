<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ProductService
{
    public function list(array $filters, int $perPage = 25): LengthAwarePaginator
    {
        $query = Product::with(['category', 'unit']);

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function show(int $id): Product
    {
        return Product::with(['category', 'unit'])->findOrFail($id);
    }

    public function create(array $data): Product
    {
        $data['is_active'] = true;
        return Product::create($data);
    }

    public function update(int $id, array $data): Product
    {
        $product = Product::findOrFail($id);
        $product->update($data);
        return $product->fresh(['category', 'unit']);
    }

    public function delete(int $id): void
    {
        Product::findOrFail($id)->delete();
    }
}
