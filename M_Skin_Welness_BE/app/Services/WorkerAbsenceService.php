<?php

namespace App\Services;

use App\Models\WorkerAbsence;
use Carbon\{CarbonImmutable, CarbonPeriod};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WorkerAbsenceService
{
    public function create(int $centerId, array $data): Collection
    {
        return DB::transaction(function () use ($centerId, $data) {
            $isFullDay = (bool) ($data['is_full_day'] ?? false);

            $from = CarbonImmutable::parse($data['from']);
            $to = CarbonImmutable::parse($data['to']);

            $absences = collect();

            foreach (CarbonPeriod::between($from, $to) as $day) {
                $absences->push(WorkerAbsence::create([
                    'center_id' => $centerId,
                    'worker_id' => $data['worker_id'],
                    'date' => $day->toDateString(),
                    'is_full_day' => $isFullDay,
                    'start_time' => $isFullDay ? null : ($data['start_time'] ?? null),
                    'end_time' => $isFullDay ? null : ($data['end_time'] ?? null),
                    'reason' => $data['reason'] ?? null,
                    'absence_type_id' => $data['absence_type_id'] ?? null,
                    'notes' => $data['notes'] ?? null,
                ]));
            }

            return $absences;
        });
    }

    public function update(WorkerAbsence $absence, array $data): WorkerAbsence
    {
        return DB::transaction(function () use ($absence, $data) {
            if (array_key_exists('is_full_day', $data) && $data['is_full_day'] === true) {
                $data['start_time'] = null;
                $data['end_time'] = null;
            }

            $absence->fill($data)->save();

            return $absence;
        });
    }

    public function delete(WorkerAbsence $absence): void
    {
        DB::transaction(function () use ($absence) {
            $absence->delete();
        });
    }
}
