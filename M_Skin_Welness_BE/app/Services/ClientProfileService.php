<?php

namespace App\Services;

use App\Models\ClientProfile;
use Illuminate\Support\Facades\DB;

class ClientProfileService
{
    public function create(int $centerId, array $data): ClientProfile
    {
        return DB::transaction(function () use ($centerId, $data) {
            $profile = ClientProfile::create([
                'center_id' => $centerId,
                'user_id' => $data['user_id'],
                'body_type' => $data['body_type'],
                'general_notes' => $data['general_notes'] ?? null,
            ]);

            return $profile->load(['client', 'currentEvaluation']);
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
