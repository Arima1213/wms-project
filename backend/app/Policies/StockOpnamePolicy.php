<?php

namespace App\Policies;

use App\Models\User;
use App\Models\StockOpname;

class StockOpnamePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view stock_opnames');
    }

    public function view(User $user, StockOpname $opname): bool
    {
        return $user->hasPermissionTo('view stock_opnames');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create stock_opnames');
    }

    public function update(User $user, StockOpname $opname): bool
    {
        return $user->hasPermissionTo('edit stock_opnames');
    }

    public function approve(User $user, StockOpname $opname): bool
    {
        return $user->hasPermissionTo('approve stock_opnames');
    }

    public function start(User $user, StockOpname $opname): bool
    {
        return $user->hasPermissionTo('edit stock_opnames');
    }

    public function submit(User $user, StockOpname $opname): bool
    {
        return $user->hasPermissionTo('edit stock_opnames');
    }

    public function cancel(User $user, StockOpname $opname): bool
    {
        return $user->hasPermissionTo('edit stock_opnames');
    }
}
