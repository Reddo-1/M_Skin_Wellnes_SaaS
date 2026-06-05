<?php

namespace App\Http\Resources;

use App\Models\{SkinEvaluation, UserFile};
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

/** @mixin SkinEvaluation */
class SkinEvaluationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'center_id' => $this->center_id,
            'user_id' => $this->user_id,
            'client_profile_id' => $this->client_profile_id,
            'skin_type_id' => $this->skin_type_id,
            'evaluation_date' => $this->evaluation_date?->toDateString(),
            'professional_id' => $this->professional_id,
            'general_notes' => $this->general_notes,
            'client' => $this->whenLoaded('client', function () {
                return [
                    'id' => $this->client->id,
                    'name' => $this->client->name,
                    'email' => $this->client->email,
                ];
            }),
            'client_profile' => $this->whenLoaded('clientProfile', function () {
                return [
                    'id' => $this->clientProfile->id,
                    'body_type' => $this->clientProfile->body_type,
                ];
            }),
            'skin_type' => $this->whenLoaded('skinType', function () {
                return ['id' => $this->skinType->id, 'name' => $this->skinType->name];
            }),
            'professional' => $this->whenLoaded('professional', function () {
                return ['id' => $this->professional->id, 'name' => $this->professional->name];
            }),
            'variations' => $this->whenLoaded('variations', function () {
                return $this->variations->map(function ($v) {
                    return ['id' => $v->id, 'name' => $v->name];
                })->all();
            }),
            //solo las imagenes clinicas (no el resto de archivos), cada una con URL firmada temporal
            'clinical_images' => $this->whenLoaded('files', function () {
                return $this->files
                    ->whereIn('category', UserFile::CATEGORIES_CLINICAL)
                    ->map(fn ($file) => [
                        'id' => $file->id,
                        'category' => $file->category,
                        'url' => URL::temporarySignedRoute(
                            'user-files.file',
                            now()->addMinutes(10),
                            ['user_file' => $file->id],
                        ),
                    ])->values()->all();
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
