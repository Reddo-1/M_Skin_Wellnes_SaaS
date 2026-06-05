<?php

namespace App\Http\Resources;

use App\Models\Treatment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Treatment */
class TreatmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'center_id' => $this->center_id,
            'name' => $this->name,
            'duration_minutes' => $this->duration_minutes,
            'margin_minutes' => $this->margin_minutes,
            'price' => $this->price,
            'is_active' => $this->is_active,
            'machines' => $this->whenLoaded('machines', function () {
                return $this->machines->map(function ($m) {
                    return [
                        'id' => $m->id,
                        'name' => $m->name,
                    ];
                })->all();
            }),
            'authorized_roles' => $this->whenLoaded('authorizedRoles', function () {
                return $this->authorizedRoles->map(function ($r) {
                    return [
                        'id' => $r->id,
                        'name' => $r->name,
                    ];
                })->all();
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
