<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Inbound;

class InboundPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inbound.view');
    }

    public function view(User $user, Inbound $inbound): bool
    {
        return $user->can('inbound.view');
    }

    public function create(User $user): bool
    {
        return $user->can('inbound.create');
    }

    public function update(User $user, Inbound $inbound): bool
    {
        return $user->can('inbound.update');
    }

    public function delete(User $user, Inbound $inbound): bool
    {
        return $user->can('inbound.delete');
    }

    public function receive(User $user, Inbound $inbound): bool
    {
        return $user->can('inbound.receive');
    }

    public function cancel(User $user, Inbound $inbound): bool
    {
        return $user->can('inbound.cancel');
    }
}
