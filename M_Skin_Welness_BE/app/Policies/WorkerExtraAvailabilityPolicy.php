<?php

namespace App\Policies;

use App\Models\{User, WorkerExtraAvailability};

class WorkerExtraAvailabilityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('worker_extra_availabilities.view');
    }

    public function view(User $user, WorkerExtraAvailability $extra): bool
    {
        return $user->center_id === $extra->center_id
            && $user->can('worker_extra_availabilities.view');
    }

    public function create(User $user): bool
    {
        return $user->can('worker_extra_availabilities.create');
    }

    public function update(User $user, WorkerExtraAvailability $extra): bool
    {
        return $user->center_id === $extra->center_id
            && $user->can('worker_extra_availabilities.update');
    }

    public function delete(User $user, WorkerExtraAvailability $extra): bool
    {
        return $user->center_id === $extra->center_id
            && $user->can('worker_extra_availabilities.delete');
    }
}
