<?php

namespace App\Services;

use App\Models\WorkerExtraAvailability;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkerExtraAvailabilityService
{
    public function create(int $centerId, array $data): WorkerExtraAvailability
    {
        return DB::transaction(function () use ($centerId, $data) {
            $exists = WorkerExtraAvailability::query()
                ->forCenter($centerId)
                ->where('worker_id', $data['worker_id'])
                ->where('date', $data['date'])
                ->where('start_time', $data['start_time'])
                ->where('end_time', $data['end_time'])
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'start_time' => ['Esa disponibilidad extra ya está asignada a este trabajador en esa fecha.'],
                ]);
            }

            return WorkerExtraAvailability::create([
                'center_id' => $centerId,
                'worker_id' => $data['worker_id'],
                'date' => $data['date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'reason' => $data['reason'] ?? null,
            ]);
        });
    }

    public function update(WorkerExtraAvailability $extra, array $data): WorkerExtraAvailability
    {
        return DB::transaction(function () use ($extra, $data) {
            $workerId = $data['worker_id'] ?? $extra->worker_id;
            $date = $data['date'] ?? $extra->date;
            $startTime = $data['start_time'] ?? $extra->start_time;
            $endTime = $data['end_time'] ?? $extra->end_time;

            $exists = WorkerExtraAvailability::query()
                ->forCenter($extra->center_id)
                ->where('worker_id', $workerId)
                ->where('date', $date)
                ->where('start_time', $startTime)
                ->where('end_time', $endTime)
                ->where('id', '!=', $extra->id)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'start_time' => ['Esa disponibilidad extra ya está asignada a este trabajador en esa fecha.'],
                ]);
            }

            $extra->fill($data)->save();

            return $extra;
        });
    }

    public function delete(WorkerExtraAvailability $extra): void
    {
        DB::transaction(function () use ($extra) {
            $extra->delete();
        });
    }
}
