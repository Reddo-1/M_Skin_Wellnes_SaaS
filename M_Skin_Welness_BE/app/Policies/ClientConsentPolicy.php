<?php

namespace App\Policies;

use App\Models\User;

class ClientConsentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('client_consents.view');
    }
}
