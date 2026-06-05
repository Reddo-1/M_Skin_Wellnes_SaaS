<?php

namespace App\Http\Resources;

use App\Models\WorkerAbsence;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkerAbsence */
class WorkerAbsenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'center_id' => $this->center_id,
            'worker_id' => $this->worker_id,
            'date' => $this->date?->toDateString(),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'is_full_day' => $this->is_full_day,
            'reason' => $this->reason,
            'absence_type_id' => $this->absence_type_id,
            'notes' => $this->notes,
            'worker' => $this->whenLoaded('worker', function () {
                return [
                    'id' => $this->worker->id,
                    'name' => $this->worker->name,
                ];
            }),
            'absence_type' => $this->whenLoaded('absenceType', function () {
                return $this->absenceType ? [
                    'id' => $this->absenceType->id,
                    'name' => $this->absenceType->name,
                ] : null;
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
