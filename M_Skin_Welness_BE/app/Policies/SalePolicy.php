<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

class SalePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sales.view');
    }

    public function view(User $user, Sale $sale): bool
    {
        if ($user->center_id !== $sale->center_id) {
            return false;
        }

        if (! $user->can('sales.view')) {
            return false;
        }

        if ($user->hasRole('cliente')) {
            return $sale->client_id === $user->id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('sales.create');
    }

    public function changeStatus(User $user, Sale $sale): bool
    {
        return $user->center_id === $sale->center_id
            && $user->can('sales.change_status');
    }
}
