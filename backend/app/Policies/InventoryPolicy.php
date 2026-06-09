<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Inventory;

class InventoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view inventory');
    }

    public function view(User $user, Inventory $inventory): bool
    {
        return $user->hasPermissionTo('view inventory');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('edit inventory');
    }

    public function update(User $user, Inventory $inventory): bool
    {
        return $user->hasPermissionTo('edit inventory');
    }

    public function delete(User $user, Inventory $inventory): bool
    {
        return $user->hasPermissionTo('edit inventory');
    }
}
