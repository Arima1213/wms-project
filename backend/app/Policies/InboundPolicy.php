<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Inbound;

class InboundPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view inbounds');
    }

    public function view(User $user, Inbound $inbound): bool
    {
        return $user->hasPermissionTo('view inbounds');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create inbounds');
    }

    public function update(User $user, Inbound $inbound): bool
    {
        return $user->hasPermissionTo('edit inbounds');
    }

    public function delete(User $user, Inbound $inbound): bool
    {
        return $user->hasPermissionTo('delete inbounds');
    }

    public function receive(User $user, Inbound $inbound): bool
    {
        return $user->hasPermissionTo('receive inbounds');
    }
}
