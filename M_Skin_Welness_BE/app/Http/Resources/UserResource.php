<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'center_id' => $this->center_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'birth_date' => $this->birth_date?->toDateString(),
            'is_active' => $this->is_active,
            'registration_source' => $this->registration_source,
            'roles' => $this->getRoleNames(),
            'permissions' => $this->getAllPermissions()->pluck('name'),
            'center' => $this->whenLoaded('center', function () {
                return [
                    'id' => $this->center->id,
                    'name' => $this->center->name,
                    'slug' => $this->center->slug,
                    'plan' => $this->center->relationLoaded('plan') && $this->center->plan !== null
                        ? PlanResource::make($this->center->plan)
                        : null,
                ];
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
