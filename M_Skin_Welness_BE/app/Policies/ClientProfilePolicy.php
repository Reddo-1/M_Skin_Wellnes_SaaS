<?php

namespace App\Policies;

use App\Models\ClientProfile;
use App\Models\User;

class ClientProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('client_profiles.view');
    }

    public function view(User $user, ClientProfile $profile): bool
    {
        return $user->center_id === $profile->center_id
            && $user->can('client_profiles.view');
    }

    public function create(User $user): bool
    {
        return $user->can('client_profiles.create');
    }

    public function update(User $user, ClientProfile $profile): bool
    {
        return $user->center_id === $profile->center_id
            && $user->can('client_profiles.update');
    }
}
