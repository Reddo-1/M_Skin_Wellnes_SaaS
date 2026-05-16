<?php

namespace App\Policies;

use App\Models\Plan;
use App\Models\User;

class PlanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('plans.view');
    }

    public function view(User $user, Plan $plan): bool
    {
        return $user->can('plans.view');
    }
}
