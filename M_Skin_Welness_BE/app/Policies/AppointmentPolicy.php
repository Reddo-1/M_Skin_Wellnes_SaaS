<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('appointments.view');
    }

    public function view(User $user, Appointment $appointment): bool
    {
        if ($user->center_id !== $appointment->center_id) {
            return false;
        }

        if (!$user->can('appointments.view')) {
            return false;
        }

        if ($user->hasRole('cliente')) {
            return $appointment->client_id === $user->id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('appointments.create');
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $user->center_id === $appointment->center_id
            && $user->can('appointments.update');
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->center_id === $appointment->center_id
            && $user->can('appointments.delete');
    }

    public function changeStatus(User $user, Appointment $appointment): bool
    {
        if ($user->center_id !== $appointment->center_id) {
            return false;
        }

        if (!$user->can('appointments.change_status')) {
            return false;
        }

        if ($user->hasRole('cliente')) {
            return $appointment->client_id === $user->id;
        }

        return true;
    }

    public function attachProducts(User $user, Appointment $appointment): bool
    {
        return $user->center_id === $appointment->center_id
            && $user->can('appointments.attach_products');
    }
}
