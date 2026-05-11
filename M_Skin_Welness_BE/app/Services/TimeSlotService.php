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
            $exists = TimeSlot::query()
                ->where('start_time', $data['start_time'])
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'start_time' => ['Ya existe una franja con esa hora de inicio.'],
                ]);
            }

            return TimeSlot::create([
                'center_id' => $centerId,
                'name' => $data['name'] ?? null,
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'is_active' => $data['is_active'] ?? true,
            ]);
        });
    }

    public function update(TimeSlot $timeSlot, array $data): TimeSlot
    {
        return DB::transaction(function () use ($timeSlot, $data) {
            if (array_key_exists('start_time', $data)) {
                $exists = TimeSlot::query()
                    ->where('start_time', $data['start_time'])
                    ->where('id', '!=', $timeSlot->id)
                    ->exists();

                if ($exists) {
                    throw ValidationException::withMessages([
                        'start_time' => ['Ya existe una franja con esa hora de inicio.'],
                    ]);
                }
            }

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
