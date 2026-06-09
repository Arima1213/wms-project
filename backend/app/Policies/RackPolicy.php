<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Rack;

class RackPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view racks');
    }

    public function view(User $user, Rack $rack): bool
    {
        return $user->hasPermissionTo('view racks');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create racks');
    }

    public function update(User $user, Rack $rack): bool
    {
        return $user->hasPermissionTo('edit racks');
    }

    public function delete(User $user, Rack $rack): bool
    {
        return $user->hasPermissionTo('delete racks');
    }
}
