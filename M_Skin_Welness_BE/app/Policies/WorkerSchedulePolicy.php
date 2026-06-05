<?php

namespace App\Policies;

use App\Models\{User, WorkerSchedule};

class WorkerSchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('worker_schedules.view');
    }

    public function view(User $user, WorkerSchedule $workerSchedule): bool
    {
        return $user->center_id === $workerSchedule->center_id
            && $user->can('worker_schedules.view');
    }

    public function create(User $user): bool
    {
        return $user->can('worker_schedules.create');
    }

    public function update(User $user, WorkerSchedule $workerSchedule): bool
    {
        return $user->center_id === $workerSchedule->center_id
            && $user->can('worker_schedules.update');
    }

    public function delete(User $user, WorkerSchedule $workerSchedule): bool
    {
        return $user->center_id === $workerSchedule->center_id
            && $user->can('worker_schedules.delete');
    }
}
