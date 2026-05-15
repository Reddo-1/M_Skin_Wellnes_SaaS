<?php

namespace App\Http\Resources;

use App\Models\ClientProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ClientProfile */
class ClientProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'center_id' => $this->center_id,
            'user_id' => $this->user_id,
            'body_type' => $this->body_type,
            'current_skin_evaluation_id' => $this->current_skin_evaluation_id,
            'general_notes' => $this->general_notes,
            'client' => $this->whenLoaded('client', function () {
                return [
                    'id' => $this->client->id,
                    'name' => $this->client->name,
                    'email' => $this->client->email,
                ];
            }),
            'current_evaluation' => $this->whenLoaded('currentEvaluation', function () {
                return SkinEvaluationResource::make($this->currentEvaluation);
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
