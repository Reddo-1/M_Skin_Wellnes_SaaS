<?php

namespace App\Http\Resources;

use App\Models\CenterFile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

/** @mixin CenterFile */
class CenterFileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'center_id' => $this->center_id,
            'type' => $this->type,
            //url firmada de 10 min servida por la api: disco privado, sin symlink ni APP_URL
            'url' => URL::temporarySignedRoute(
                'center-files.file',
                now()->addMinutes(10),
                ['center_file' => $this->id],
            ),
            'mime_type' => $this->mime_type,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
