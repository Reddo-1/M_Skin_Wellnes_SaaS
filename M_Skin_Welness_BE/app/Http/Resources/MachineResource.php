<?php

namespace App\Http\Resources;

use App\Models\Machine;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Machine */
class MachineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'center_id' => $this->center_id,
            'name' => $this->name,
            'is_mobile' => $this->is_mobile,
            'fixed_room_id' => $this->fixed_room_id,
            'is_active' => $this->is_active,
            'fixed_room' => $this->whenLoaded('fixedRoom', function () {
                return $this->fixedRoom ? [
                    'id' => $this->fixedRoom->id,
                    'name' => $this->fixedRoom->name,
                ] : null;
            }),
            'treatments' => $this->whenLoaded('treatments', function () {
                return $this->treatments->map(function ($t) {
                    return [
                        'id' => $t->id,
                        'name' => $t->name,
                    ];
                })->all();
            }),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
