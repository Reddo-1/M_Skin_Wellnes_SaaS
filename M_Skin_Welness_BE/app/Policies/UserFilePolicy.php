<?php

namespace App\Policies;

use App\Models\{User, UserFile};

class UserFilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('user_files.view');
    }

    public function view(User $user, UserFile $file): bool
    {
        if ($user->center_id !== $file->center_id) {
            return false;
        }
        if ($user->id === $file->user_id) {
            return true;
        }
        //un cliente puro solo accede a sus propios ficheros, nunca a los de otro cliente del centro
        if ($user->hasRole('cliente') && $user->roles->count() === 1) {
            return false;
        }

        return $user->can('user_files.view');
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function delete(User $user, UserFile $file): bool
    {
        if ($user->center_id !== $file->center_id) {
            return false;
        }

        //el avatar propio se borra sin permiso user_files.delete
        if ($file->isAvatar() && $user->id === $file->user_id) {
            return true;
        }

        return $user->can('user_files.delete');
    }
}
