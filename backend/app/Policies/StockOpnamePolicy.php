<?php

namespace App\Policies;

use App\Models\User;
use App\Models\StockOpname;

class StockOpnamePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('stock_opname.view');
    }

    public function view(User $user, StockOpname $opname): bool
    {
        return $user->can('stock_opname.view');
    }

    public function create(User $user): bool
    {
        return $user->can('stock_opname.create');
    }

    public function update(User $user, StockOpname $opname): bool
    {
        return $user->can('stock_opname.submit');
    }

    public function approve(User $user, StockOpname $opname): bool
    {
        return $user->can('stock_opname.approve');
    }

    public function start(User $user, StockOpname $opname): bool
    {
        return $user->can('stock_opname.start');
    }

    public function submit(User $user, StockOpname $opname): bool
    {
        return $user->can('stock_opname.submit');
    }

    public function cancel(User $user, StockOpname $opname): bool
    {
        return $user->can('stock_opname.submit');
    }
}
