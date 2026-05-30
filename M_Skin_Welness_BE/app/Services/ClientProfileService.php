<?php

namespace App\Services;

use App\Models\ClientProfile;
use Illuminate\Support\Facades\DB;

class ClientProfileService
{
    public function __construct(private readonly SkinEvaluationService $evaluations)
    {
    }

    public function create(int $centerId, int $actorId, array $data): ClientProfile
    {
        return DB::transaction(function () use ($centerId, $actorId, $data) {
            $profile = ClientProfile::create([
                'center_id' => $centerId,
                'user_id' => $data['user_id'],
                'body_type' => $data['body_type'],
                'general_notes' => $data['general_notes'] ?? null,
            ]);

            //la ficha nace con su primera evaluacion, que queda como la vigente del perfil
            $evaluationData = $data['evaluation'];
            $evaluationData['client_profile_id'] = $profile->id;
            $this->evaluations->create($centerId, $actorId, $evaluationData);

            return $profile->refresh()->load([
                'client',
                'currentEvaluation.skinType',
                'currentEvaluation.variations',
                'currentEvaluation.professional',
            ]);
        });
    }

    public function update(ClientProfile $profile, array $data): ClientProfile
    {
        return DB::transaction(function () use ($profile, $data) {
            $profile->fill($data)->save();

            return $profile->load(['client', 'currentEvaluation']);
        });
    }
}
