<?php

namespace App\Http\Resources;

use App\Models\WorkerSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkerSchedule */
class WorkerScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'center_id' => $this->center_id,
            'worker_id' => $this->worker_id,
            'weekday' => $this->weekday,
            'time_slot_id' => $this->time_slot_id,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'worker' => $this->whenLoaded('worker', function () {
                return [
                    'id' => $this->worker->id,
                    'name' => $this->worker->name,
                ];
            }),
            'time_slot' => $this->whenLoaded('timeSlot', function () {
                return [
                    'id' => $this->timeSlot->id,
                    'name' => $this->timeSlot->name,
                    'start_time' => $this->timeSlot->start_time,
                    'end_time' => $this->timeSlot->end_time,
                    'break_start' => $this->timeSlot->break_start,
                    'break_end' => $this->timeSlot->break_end,
                ];
            }),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
