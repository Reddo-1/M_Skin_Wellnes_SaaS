<?php

namespace App\Services;

use App\Models\{ClientConsent, UserFile};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ClientConsentService
{
    public function __construct(private readonly UserFileService $userFiles)
    {
    }

    public function create(int $centerId, int $actorId, array $data, UploadedFile $signature): ClientConsent
    {
        return DB::transaction(function () use ($centerId, $actorId, $data, $signature) {
            $userId = (int) $data['user_id'];

            ClientConsent::query()
                ->forCenter($centerId)
                ->where('user_id', $userId)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $signatureFile = $this->userFiles->upload(
                centerId: $centerId,
                data: [
                    'user_id' => $userId,
                    'category' => UserFile::CATEGORY_CONSENT_SIGNATURE,
                ],
                file: $signature,
            );

            $consent = ClientConsent::create([
                'center_id' => $centerId,
                'user_id' => $userId,
                'reviewed_by_user_id' => $actorId,
                'clinical_photos_consent' => (bool) $data['clinical_photos_consent'],
                'marketing_data_consent' => (bool) $data['marketing_data_consent'],
                'commercial_images_consent' => (bool) $data['commercial_images_consent'],
                'signature_user_file_id' => $signatureFile->id,
                'signed_at' => now(),
                'notes' => $data['notes'] ?? null,
                'is_active' => true,
            ]);

            return $consent->load(['client', 'reviewer', 'signatureFile']);
        });
    }

    public function update(ClientConsent $consent, array $data): ClientConsent
    {
        return DB::transaction(function () use ($consent, $data) {
            $consent->fill($data)->save();

            return $consent->load(['client', 'reviewer', 'signatureFile']);
        });
    }
}
