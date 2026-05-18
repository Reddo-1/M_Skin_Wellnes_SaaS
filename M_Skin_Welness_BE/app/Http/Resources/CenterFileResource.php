<?php

namespace App\Http\Resources;

use App\Models\CenterFile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CenterFile */
class CenterFileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'center_id' => $this->center_id,
            'type' => $this->type,
            'path' => $this->path,
            'url' => asset('storage/'.$this->path),
            'mime_type' => $this->mime_type,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
