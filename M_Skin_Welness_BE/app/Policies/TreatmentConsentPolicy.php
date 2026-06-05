<?php

namespace App\Policies;

use App\Models\{TreatmentConsent, User};

class TreatmentConsentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('treatment_consents.view');
    }

    //solo la aptitud: el diagnosticador la fija desde la ficha (no toca el consentimiento del cliente)
    public function update(User $user, TreatmentConsent $treatmentConsent): bool
    {
        return $user->center_id === $treatmentConsent->center_id
            && $user->can('treatment_consents.update');
    }
}
