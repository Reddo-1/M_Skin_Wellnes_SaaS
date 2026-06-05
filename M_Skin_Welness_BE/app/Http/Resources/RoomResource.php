<?php

namespace App\Http\Resources;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Room */
class RoomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'center_id' => $this->center_id,
            'name' => $this->name,
            'grid_position' => $this->grid_position,
            'is_active' => $this->is_active,
            'machines' => $this->whenLoaded('machines', function () {
                return $this->machines->map(function ($m) {
                    return [
                        'id' => $m->id,
                        'name' => $m->name,
                    ];
                })->all();
            }),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
