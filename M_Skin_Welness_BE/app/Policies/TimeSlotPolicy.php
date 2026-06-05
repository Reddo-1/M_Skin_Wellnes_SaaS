<?php

namespace App\Policies;

use App\Models\{TimeSlot, User};

class TimeSlotPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('time_slots.view');
    }

    public function view(User $user, TimeSlot $timeSlot): bool
    {
        return $user->center_id === $timeSlot->center_id
            && $user->can('time_slots.view');
    }

    public function create(User $user): bool
    {
        return $user->can('time_slots.create');
    }

    public function update(User $user, TimeSlot $timeSlot): bool
    {
        return $user->center_id === $timeSlot->center_id
            && $user->can('time_slots.update');
    }

    public function delete(User $user, TimeSlot $timeSlot): bool
    {
        return $user->center_id === $timeSlot->center_id
            && $user->can('time_slots.delete');
    }
}
