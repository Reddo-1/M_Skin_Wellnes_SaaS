<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    public function view(User $user, User $target): bool
    {
        if ($user->id === $target->id) {
            return true;
        }

        return $user->center_id === $target->center_id
            && $user->can('users.view');
    }

    public function create(User $user): bool
    {
        return $user->can('users.create_staff')
            || $user->can('users.create_client');
    }

    public function update(User $user, User $target): bool
    {
        if ($user->id === $target->id) {
            return true;
        }

        return $user->center_id === $target->center_id
            && $user->can('users.update');
    }

    public function deactivate(User $user, User $target): bool
    {
        if ($user->id === $target->id) {
            return false;
        }

        return $user->center_id === $target->center_id
            && $user->can('users.deactivate');
    }

    public function activate(User $user, User $target): bool
    {
        return $user->center_id === $target->center_id
            && $user->can('users.deactivate');
    }

    //solo el propio usuario puede cambiar su contraseña; el reset por olvido va por el mailer
    public function changePassword(User $user, User $target): bool
    {
        return $user->id === $target->id;
    }

    //la validacion fina de que roles concretos puede asignar la hace el FormRequest
    public function manageRoles(User $user, User $target): bool
    {
        if ($user->center_id !== $target->center_id) {
            return false;
        }

        return $user->can('users.create_staff') || $user->can('users.create_client');
    }
}
