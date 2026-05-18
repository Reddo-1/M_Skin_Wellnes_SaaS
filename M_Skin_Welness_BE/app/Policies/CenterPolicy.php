<?php

namespace App\Policies;

use App\Models\Center;
use App\Models\User;

class CenterPolicy
{
    public function view(User $user, Center $center): bool
    {
        if (! $user->can('centers.view')) {
            return false;
        }

        return $user->center_id === $center->id;
    }

    public function update(User $user, Center $center): bool
    {
        if (! $user->can('centers.update')) {
            return false;
        }

        return $user->center_id === $center->id;
    }
}
