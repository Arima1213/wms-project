<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Outbound;

class OutboundPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view outbounds');
    }

    public function view(User $user, Outbound $outbound): bool
    {
        return $user->hasPermissionTo('view outbounds');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create outbounds');
    }

    public function update(User $user, Outbound $outbound): bool
    {
        return $user->hasPermissionTo('edit outbounds');
    }

    public function delete(User $user, Outbound $outbound): bool
    {
        return $user->hasPermissionTo('delete outbounds');
    }

    public function ship(User $user, Outbound $outbound): bool
    {
        return $user->hasPermissionTo('ship outbounds');
    }
}
