<?php

namespace App\Policies;

use App\Models\{Treatment, User};

class TreatmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('treatments.view');
    }

    public function view(User $user, Treatment $treatment): bool
    {
        return $user->center_id === $treatment->center_id
            && $user->can('treatments.view');
    }

    public function create(User $user): bool
    {
        return $user->can('treatments.create');
    }

    public function update(User $user, Treatment $treatment): bool
    {
        return $user->center_id === $treatment->center_id
            && $user->can('treatments.update');
    }

    public function delete(User $user, Treatment $treatment): bool
    {
        return $user->center_id === $treatment->center_id
            && $user->can('treatments.delete');
    }
}
