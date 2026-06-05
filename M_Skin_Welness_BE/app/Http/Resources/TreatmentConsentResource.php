<?php

namespace App\Http\Resources;

use App\Models\TreatmentConsent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TreatmentConsent */
class TreatmentConsentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'center_id' => $this->center_id,
            'user_id' => $this->user_id,
            'treatment_id' => $this->treatment_id,
            'reviewed_by_user_id' => $this->reviewed_by_user_id,
            'review_date' => $this->review_date?->toDateString(),
            'is_suitable' => $this->is_suitable,
            'unsuitability_reason' => $this->unsuitability_reason,
            'treatment_consent' => $this->treatment_consent,
            'notes' => $this->notes,
            'is_active' => $this->is_active,
            'client' => $this->whenLoaded('client', function () {
                return [
                    'id' => $this->client->id,
                    'name' => $this->client->name,
                    'email' => $this->client->email,
                ];
            }),
            'treatment' => $this->whenLoaded('treatment', function () {
                return ['id' => $this->treatment->id, 'name' => $this->treatment->name];
            }),
            'reviewer' => $this->whenLoaded('reviewer', function () {
                return ['id' => $this->reviewer->id, 'name' => $this->reviewer->name];
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
