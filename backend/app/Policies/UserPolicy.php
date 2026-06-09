<?php

namespace App\Policies;

use App\Models\User;
use App\Models\User as UserModel;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view users');
    }

    public function view(User $user, UserModel $targetUser): bool
    {
        return $user->hasPermissionTo('view users');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create users');
    }

    public function update(User $user, UserModel $targetUser): bool
    {
        return $user->hasPermissionTo('edit users');
    }

    public function delete(User $user, UserModel $targetUser): bool
    {
        return $user->hasPermissionTo('delete users');
    }
}
