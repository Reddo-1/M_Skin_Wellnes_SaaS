<?php

namespace App\Policies;

use App\Models\{CenterFile, User};

class CenterFilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('center_files.view');
    }

    public function view(User $user, CenterFile $file): bool
    {
        if ($user->center_id !== $file->center_id) {
            return false;
        }

        return $user->can('center_files.view');
    }

    public function create(User $user): bool
    {
        return $user->can('center_files.upload');
    }

    public function delete(User $user, CenterFile $file): bool
    {
        if ($user->center_id !== $file->center_id) {
            return false;
        }

        return $user->can('center_files.delete');
    }
}
