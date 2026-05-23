<?php

namespace App\Policies;

use App\Models\{SkinEvaluation, User};

class SkinEvaluationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('skin_evaluations.view');
    }

    public function view(User $user, SkinEvaluation $evaluation): bool
    {
        return $user->center_id === $evaluation->center_id
            && $user->can('skin_evaluations.view');
    }

    public function create(User $user): bool
    {
        return $user->can('skin_evaluations.create');
    }

    public function update(User $user, SkinEvaluation $evaluation): bool
    {
        return $user->center_id === $evaluation->center_id
            && $user->can('skin_evaluations.update');
    }
}
