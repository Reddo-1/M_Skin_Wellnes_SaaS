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

        //cualquiera puede borrar su propio avatar
        if ($file->isAvatar() && $user->id === $file->user_id) {
            return true;
        }

        return $user->can('user_files.delete');
    }
}
