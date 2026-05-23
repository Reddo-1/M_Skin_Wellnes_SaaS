<?php

namespace App\Policies;

use App\Models\{ClientConsent, User};

class ClientConsentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('client_consents.view');
    }

    public function view(User $user, ClientConsent $consent): bool
    {
        return $user->center_id === $consent->center_id
            && $user->can('client_consents.view');
    }

    public function create(User $user): bool
    {
        return $user->can('client_consents.create');
    }

    public function update(User $user, ClientConsent $consent): bool
    {
        return $user->center_id === $consent->center_id
            && $user->can('client_consents.update');
    }
}
