<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Zone;

class ZonePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view zones');
    }

    public function view(User $user, Zone $zone): bool
    {
        return $user->hasPermissionTo('view zones');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create zones');
    }

    public function update(User $user, Zone $zone): bool
    {
        return $user->hasPermissionTo('edit zones');
    }

    public function delete(User $user, Zone $zone): bool
    {
        return $user->hasPermissionTo('delete zones');
    }

    public function activate(User $user, Zone $zone): bool
    {
        return $user->hasPermissionTo('edit zones');
    }

    public function deactivate(User $user, Zone $zone): bool
    {
        return $user->hasPermissionTo('edit zones');
    }
}
