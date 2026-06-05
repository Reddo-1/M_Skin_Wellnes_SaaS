<?php

namespace App\Services;

use App\Models\TimeSlot;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TimeSlotService
{
    public function create(int $centerId, array $data): TimeSlot
    {
        return DB::transaction(function () use ($centerId, $data) {
            return TimeSlot::create([
                ...$data,
                'center_id' => $centerId,
            ]);
        });
    }

    public function update(TimeSlot $timeSlot, array $data): TimeSlot
    {
        return DB::transaction(function () use ($timeSlot, $data) {
            $timeSlot->fill($data)->save();

            return $timeSlot;
        });
    }

    public function delete(TimeSlot $timeSlot): void
    {
        if ($timeSlot->workerSchedules()->exists()) {
            throw ValidationException::withMessages([
                'time_slot' => ['No se puede borrar la franja porque está asignada a horarios de trabajadores. Desactívala en su lugar.'],
            ]);
        }

        DB::transaction(function () use ($timeSlot) {
            $timeSlot->delete();
        });
    }
}
