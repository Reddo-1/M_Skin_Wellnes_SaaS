<?php

namespace App\Http\Resources;

use App\Models\ClientConsent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

/** @mixin ClientConsent */
class ClientConsentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'center_id' => $this->center_id,
            'user_id' => $this->user_id,
            'reviewed_by_user_id' => $this->reviewed_by_user_id,
            'clinical_photos_consent' => $this->clinical_photos_consent,
            'marketing_data_consent' => $this->marketing_data_consent,
            'commercial_images_consent' => $this->commercial_images_consent,
            'signature_user_file_id' => $this->signature_user_file_id,
            'signature_url' => $this->signature_user_file_id !== null
                ? URL::temporarySignedRoute(
                    'user-files.file',
                    now()->addMinutes(10),
                    ['user_file' => $this->signature_user_file_id],
                )
                : null,
            'pdf_user_file_id' => $this->pdf_user_file_id,
            'pdf_url' => $this->pdf_user_file_id !== null
                ? URL::temporarySignedRoute(
                    'user-files.file',
                    now()->addMinutes(10),
                    ['user_file' => $this->pdf_user_file_id],
                )
                : null,
            'signed_at' => $this->signed_at?->toIso8601String(),
            'notes' => $this->notes,
            'is_active' => $this->is_active,
            'client' => $this->whenLoaded('client', function () {
                return [
                    'id' => $this->client->id,
                    'name' => $this->client->name,
                    'email' => $this->client->email,
                ];
            }),
            'reviewer' => $this->whenLoaded('reviewer', function () {
                return ['id' => $this->reviewer->id, 'name' => $this->reviewer->name];
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
