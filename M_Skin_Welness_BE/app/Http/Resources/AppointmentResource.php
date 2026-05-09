<?php

namespace App\Http\Resources;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Appointment */
class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'center_id' => $this->center_id,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'actual_duration_minutes' => $this->actual_duration_minutes,
            'booking_source' => $this->booking_source,
            'reserved_price' => $this->reserved_price,
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'notes' => $this->notes,
            'status' => $this->whenLoaded('status', function () {
                return [
                    'id' => $this->status->id,
                    'name' => $this->status->name,
                ];
            }),
            'treatment' => $this->whenLoaded('treatment', function () {
                return [
                    'id' => $this->treatment->id,
                    'name' => $this->treatment->name,
                    'duration_minutes' => $this->treatment->duration_minutes,
                    'price' => $this->treatment->price,
                ];
            }),
            'room' => $this->whenLoaded('room', function () {
                return [
                    'id' => $this->room->id,
                    'name' => $this->room->name,
                ];
            }),
            'machine' => $this->whenLoaded('machine', function () {
                return $this->machine ? [
                    'id' => $this->machine->id,
                    'name' => $this->machine->name,
                ] : null;
            }),
            'client' => $this->whenLoaded('client', function () {
                return [
                    'id' => $this->client->id,
                    'name' => $this->client->name,
                    'email' => $this->client->email,
                ];
            }),
            'worker' => $this->whenLoaded('worker', function () {
                return [
                    'id' => $this->worker->id,
                    'name' => $this->worker->name,
                ];
            }),
            'assistants' => $this->whenLoaded('assistants', function () {
                return $this->assistants->map(function ($u) {
                    return [
                        'id' => $u->id,
                        'name' => $u->name,
                        'notes' => $u->pivot->notes,
                    ];
                })->all();
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
