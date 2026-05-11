<?php

namespace App\Services;

use App\Models\TimeSlot;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TimeSlotService
{
    //Evita que haya franjas con la misma hora de comienzo/finalización en el mismo centro.
    private function guardAgainstDuplicate(int $centerId, string $startTime, string $endTime, ?int $ignoreId = null): void
    {
        $query = TimeSlot::query()
            ->where('center_id', $centerId)
            ->where('start_time', $startTime)
            ->where('end_time', $endTime);

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'start_time' => ['Ya existe una franja horaria con esos mismos tiempos.'],
            ]);
        }
    }

    public function create(int $centerId, array $data): TimeSlot
    {
        return DB::transaction(function () use ($centerId, $data) {
            $this->guardAgainstDuplicate(
                centerId: $centerId,
                startTime: $data['start_time'],
                endTime: $data['end_time'],
            );

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
            $startTime = $data['start_time'] ?? $timeSlot->start_time;
            $endTime = $data['end_time'] ?? $timeSlot->end_time;

            $this->guardAgainstDuplicate(
                centerId: $timeSlot->center_id,
                startTime: $startTime,
                endTime: $endTime,
                ignoreId: $timeSlot->id,
            );

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
