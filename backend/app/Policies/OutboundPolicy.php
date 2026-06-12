<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Outbound;

class OutboundPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('outbound.view');
    }

    public function view(User $user, Outbound $outbound): bool
    {
        return $user->can('outbound.view');
    }

    public function create(User $user): bool
    {
        return $user->can('outbound.create');
    }

    public function update(User $user, Outbound $outbound): bool
    {
        return $user->can('outbound.update');
    }

    public function delete(User $user, Outbound $outbound): bool
    {
        return $user->can('outbound.delete');
    }

    public function pick(User $user, Outbound $outbound): bool
    {
        return $user->can('outbound.pick');
    }

    public function ship(User $user, Outbound $outbound): bool
    {
        return $user->can('outbound.ship');
    }

    public function cancel(User $user, Outbound $outbound): bool
    {
        return $user->can('outbound.cancel');
    }
}
