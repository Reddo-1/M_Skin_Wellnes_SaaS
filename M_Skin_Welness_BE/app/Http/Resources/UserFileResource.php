<?php

namespace App\Http\Resources;

use App\Models\UserFile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

/** @mixin UserFile */
class UserFileResource extends JsonResource
{
    //duracion de las URLs firmadas; suficiente para que el FE pinte la imagen
    private const SIGNED_URL_TTL_MINUTES = 10;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'center_id' => $this->center_id,
            'user_id' => $this->user_id,
            'skin_evaluation_id' => $this->skin_evaluation_id,
            'category' => $this->category,
            //la URL caduca a los 10 minutos; al recargar el listado se genera otra
            'url' => URL::temporarySignedRoute(
                'user-files.file',
                now()->addMinutes(self::SIGNED_URL_TTL_MINUTES),
                ['user_file' => $this->id],
            ),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
