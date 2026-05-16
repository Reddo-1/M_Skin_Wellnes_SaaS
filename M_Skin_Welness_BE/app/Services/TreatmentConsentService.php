<?php

namespace App\Services;

use App\Models\TreatmentConsent;
use Illuminate\Support\Facades\DB;

class TreatmentConsentService
{
    public function create(int $centerId, int $actorId, array $data): TreatmentConsent
    {
        return DB::transaction(function () use ($centerId, $actorId, $data) {
            $userId = (int) $data['user_id'];
            $treatmentId = (int) $data['treatment_id'];

            TreatmentConsent::query()
                ->forCenter($centerId)
                ->where('user_id', $userId)
                ->where('treatment_id', $treatmentId)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $consent = TreatmentConsent::create([
                'center_id' => $centerId,
                'user_id' => $userId,
                'treatment_id' => $treatmentId,
                'reviewed_by_user_id' => $actorId,
                'review_date' => $data['review_date'] ?? now()->toDateString(),
                'is_suitable' => (bool) $data['is_suitable'],
                'unsuitability_reason' => $data['unsuitability_reason'] ?? null,
                'treatment_consent' => (bool) $data['treatment_consent'],
                'notes' => $data['notes'] ?? null,
                'is_active' => true,
            ]);

            return $consent->load(['client', 'treatment', 'reviewer']);
        });
    }

    public function update(TreatmentConsent $consent, array $data): TreatmentConsent
    {
        return DB::transaction(function () use ($consent, $data) {
            $consent->fill($data)->save();

            return $consent->load(['client', 'treatment', 'reviewer']);
        });
    }
}
