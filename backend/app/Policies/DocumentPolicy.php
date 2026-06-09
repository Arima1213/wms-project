<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Document;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view documents');
    }

    public function view(User $user, Document $document): bool
    {
        return $user->hasPermissionTo('view documents');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('upload documents');
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->hasPermissionTo('delete documents');
    }
}
