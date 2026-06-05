<?php

namespace App\Policies;

use App\Models\{User, WorkerAbsence};

class WorkerAbsencePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('worker_absences.view');
    }

    public function view(User $user, WorkerAbsence $workerAbsence): bool
    {
        return $user->center_id === $workerAbsence->center_id
            && $user->can('worker_absences.view');
    }

    public function create(User $user): bool
    {
        return $user->can('worker_absences.create');
    }

    public function update(User $user, WorkerAbsence $workerAbsence): bool
    {
        return $user->center_id === $workerAbsence->center_id
            && $user->can('worker_absences.update');
    }

    public function delete(User $user, WorkerAbsence $workerAbsence): bool
    {
        return $user->center_id === $workerAbsence->center_id
            && $user->can('worker_absences.delete');
    }
}
