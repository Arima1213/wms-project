<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Planogram;

class PlanogramPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view planograms');
    }

    public function view(User $user, Planogram $planogram): bool
    {
        return $user->hasPermissionTo('view planograms');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create planograms');
    }

    public function update(User $user, Planogram $planogram): bool
    {
        return $user->hasPermissionTo('edit planograms');
    }

    public function delete(User $user, Planogram $planogram): bool
    {
        return $user->hasPermissionTo('delete planograms');
    }

    public function snapshot(User $user, Planogram $planogram): bool
    {
        return $user->hasPermissionTo('edit planograms');
    }
}
