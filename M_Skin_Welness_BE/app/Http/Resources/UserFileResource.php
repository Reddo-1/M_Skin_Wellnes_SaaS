<?php

namespace App\Http\Resources;

use App\Models\UserFile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

/** @mixin UserFile */
class UserFileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'center_id' => $this->center_id,
            'user_id' => $this->user_id,
            'skin_evaluation_id' => $this->skin_evaluation_id,
            'category' => $this->category,
            //url firmada temporal: caduca a los 10 min y se regenera en cada carga
            'url' => URL::temporarySignedRoute(
                'user-files.file',
                now()->addMinutes(10),
                ['user_file' => $this->id],
            ),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
