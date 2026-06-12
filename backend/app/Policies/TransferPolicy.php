<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Transfer;

class TransferPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('transfer.view');
    }

    public function view(User $user, Transfer $transfer): bool
    {
        return $user->can('transfer.view');
    }

    public function create(User $user): bool
    {
        return $user->can('transfer.create');
    }

    public function approve(User $user, Transfer $transfer): bool
    {
        return $user->can('transfer.approve');
    }

    public function reject(User $user, Transfer $transfer): bool
    {
        return $user->can('transfer.reject');
    }

    public function execute(User $user, Transfer $transfer): bool
    {
        return $user->can('transfer.execute');
    }
}
