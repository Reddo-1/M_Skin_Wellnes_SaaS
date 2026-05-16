<?php

namespace App\Policies;

use App\Models\StockMovement;
use App\Models\User;

class StockMovementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('stock_movements.view');
    }

    public function view(User $user, StockMovement $movement): bool
    {
        return $user->center_id === $movement->center_id
            && $user->can('stock_movements.view');
    }

    public function create(User $user): bool
    {
        return $user->can('stock_movements.create');
    }
}
