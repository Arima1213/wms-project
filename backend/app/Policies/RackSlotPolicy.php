<?php

namespace App\Policies;

use App\Models\User;
use App\Models\RackSlot;

class RackSlotPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view racks');
    }

    public function view(User $user, RackSlot $slot): bool
    {
        return $user->hasPermissionTo('view racks');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create racks');
    }

    public function update(User $user, RackSlot $slot): bool
    {
        return $user->hasPermissionTo('edit racks');
    }

    public function delete(User $user, RackSlot $slot): bool
    {
        return $user->hasPermissionTo('delete racks');
    }

    public function assignProduct(User $user, RackSlot $slot): bool
    {
        return $user->hasPermissionTo('edit racks');
    }

    public function unassignProduct(User $user, RackSlot $slot): bool
    {
        return $user->hasPermissionTo('edit racks');
    }

    public function reserve(User $user, RackSlot $slot): bool
    {
        return $user->hasPermissionTo('edit racks');
    }
}
