<?php

namespace App\Policies;

use App\Models\TreatmentConsent;
use App\Models\User;

class TreatmentConsentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('treatment_consents.view');
    }

    public function view(User $user, TreatmentConsent $consent): bool
    {
        return $user->center_id === $consent->center_id
            && $user->can('treatment_consents.view');
    }

    public function create(User $user): bool
    {
        return $user->can('treatment_consents.create');
    }

    public function update(User $user, TreatmentConsent $consent): bool
    {
        return $user->center_id === $consent->center_id
            && $user->can('treatment_consents.update');
    }
}
