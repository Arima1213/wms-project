<?php

namespace App\Policies;

use App\Models\User;

class SettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view settings');
    }

    public function update(User $user): bool
    {
        return $user->hasPermissionTo('edit settings');
    }
}
