<?php

namespace App\Http\Resources;

use App\Models\TimeSlot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TimeSlot */
class TimeSlotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'center_id' => $this->center_id,
            'name' => $this->name,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'break_start' => $this->break_start,
            'break_end' => $this->break_end,
            'is_active' => $this->is_active,
        ];
    }
}
