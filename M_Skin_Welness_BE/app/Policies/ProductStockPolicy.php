<?php

namespace App\Policies;

use App\Models\ProductStock;
use App\Models\User;

class ProductStockPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('product_stocks.view');
    }

    public function view(User $user, ProductStock $stock): bool
    {
        return $user->center_id === $stock->center_id
            && $user->can('product_stocks.view');
    }

    public function adjust(User $user, ProductStock $stock): bool
    {
        return $user->center_id === $stock->center_id
            && $user->can('product_stocks.adjust');
    }
}
