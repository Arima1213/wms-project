<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Transfer;

class TransferPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view transfers');
    }

    public function view(User $user, Transfer $transfer): bool
    {
        return $user->hasPermissionTo('view transfers');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create transfers');
    }

    public function approve(User $user, Transfer $transfer): bool
    {
        return $user->hasPermissionTo('approve transfers');
    }

    public function execute(User $user, Transfer $transfer): bool
    {
        return $user->hasPermissionTo('execute transfers');
    }
}
