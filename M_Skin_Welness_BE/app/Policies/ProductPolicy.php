<?php

namespace App\Policies;

use App\Models\{Product, User};

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('products.view');
    }

    public function view(User $user, Product $product): bool
    {
        return $user->center_id === $product->center_id
            && $user->can('products.view');
    }

    public function create(User $user): bool
    {
        return $user->can('products.create');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->center_id === $product->center_id
            && $user->can('products.update');
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->center_id === $product->center_id
            && $user->can('products.delete');
    }
}
