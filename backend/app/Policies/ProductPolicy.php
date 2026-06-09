<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Product;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view products');
    }

    public function view(User $user, Product $product): bool
    {
        return $user->hasPermissionTo('view products');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create products');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->hasPermissionTo('edit products');
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->hasPermissionTo('delete products');
    }

    public function import(User $user): bool
    {
        return $user->hasPermissionTo('import products');
    }
}
