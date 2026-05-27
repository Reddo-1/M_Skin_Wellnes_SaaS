<?php

namespace App\Policies;

use App\Models\User;

class TreatmentConsentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('treatment_consents.view');
    }
}
