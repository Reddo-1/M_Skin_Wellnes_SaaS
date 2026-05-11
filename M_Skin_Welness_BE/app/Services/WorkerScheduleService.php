<?php

namespace App\Services;

use App\Models\WorkerSchedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkerScheduleService
{
    public function create(int $centerId, array $data): WorkerSchedule
    {
        return DB::transaction(function () use ($centerId, $data) {
            $exists = WorkerSchedule::query()
                ->forCenter($centerId)
                ->where('worker_id', $data['worker_id'])
                ->where('weekday', $data['weekday'])
                ->where('time_slot_id', $data['time_slot_id'])
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'time_slot_id' => ['Esa franja ya está asignada a este trabajador en ese día.'],
                ]);
            }

            return WorkerSchedule::create([
                'center_id' => $centerId,
                'worker_id' => $data['worker_id'],
                'weekday' => $data['weekday'],
                'time_slot_id' => $data['time_slot_id'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? null,
            ]);
        });
    }

    public function update(WorkerSchedule $schedule, array $data): WorkerSchedule
    {
        return DB::transaction(function () use ($schedule, $data) {
            $workerId = $data['worker_id'] ?? $schedule->worker_id;
            $weekday = $data['weekday'] ?? $schedule->weekday;
            $timeSlotId = $data['time_slot_id'] ?? $schedule->time_slot_id;

            $exists = WorkerSchedule::query()
                ->forCenter($schedule->center_id)
                ->where('worker_id', $workerId)
                ->where('weekday', $weekday)
                ->where('time_slot_id', $timeSlotId)
                ->where('id', '!=', $schedule->id)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'time_slot_id' => ['Esa franja ya está asignada a este trabajador en ese día.'],
                ]);
            }

            $schedule->fill($data)->save();

            return $schedule;
        });
    }

    public function delete(WorkerSchedule $schedule): void
    {
        DB::transaction(function () use ($schedule) {
            $schedule->delete();
        });
    }
}
