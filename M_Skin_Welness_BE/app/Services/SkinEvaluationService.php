<?php

namespace App\Services;

use App\Models\{ClientProfile, SkinEvaluation};
use Illuminate\Support\Facades\DB;

class SkinEvaluationService
{
    public function create(int $centerId, int $actorId, array $data): SkinEvaluation
    {
        return DB::transaction(function () use ($centerId, $actorId, $data) {
            $profile = ClientProfile::query()
                ->forCenter($centerId)
                ->whereKey((int) $data['client_profile_id'])
                ->firstOrFail();

            $evaluationDate = $data['evaluation_date'] ?? now()->toDateString();

            $evaluation = SkinEvaluation::create([
                'center_id' => $centerId,
                'user_id' => $profile->user_id,
                'client_profile_id' => $profile->id,
                'skin_type_id' => $data['skin_type_id'],
                'evaluation_date' => $evaluationDate,
                'professional_id' => $actorId,
                'general_notes' => $data['general_notes'] ?? null,
            ]);

            if (! empty($data['variation_ids'])) {
                $evaluation->variations()->sync($data['variation_ids']);
            }

            //la nueva evaluacion pasa a ser el estado vigente de la ficha
            $profile->current_skin_evaluation_id = $evaluation->id;
            $profile->save();

            return $evaluation->load(['client', 'clientProfile', 'skinType', 'professional', 'variations']);
        });
    }

    //correccion de errores: solo retoca la evaluacion, no cambia el puntero del perfil
    public function update(SkinEvaluation $evaluation, array $data): SkinEvaluation
    {
        return DB::transaction(function () use ($evaluation, $data) {
            $evaluation->fill($data)->save();

            if (array_key_exists('variation_ids', $data)) {
                $evaluation->variations()->sync($data['variation_ids'] ?? []);
            }

            return $evaluation->load(['client', 'clientProfile', 'skinType', 'professional', 'variations']);
        });
    }
}
