<?php

namespace Database\Seeders;

use App\Models\{Center, User};
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorkerAbsenceSeeder extends Seeder
{
    public function run(): void
    {
        $centerId = Center::query()->where('slug', 'demo')->value('id');

        if ($centerId === null) {
            return;
        }

        $absenceTypes = DB::table('absence_types')->pluck('id', 'name')->all();
        $today = CarbonImmutable::today('Europe/Madrid');

        //rol, offset en dias desde hoy, dia completo, inicio, fin, tipo
        $absences = [
            ['diagnosticador',   -9, true,  null,    null,    'justificada'],
            ['dermo_esteticien',  6, false, '10:00', '12:00', 'retribuida'],
            ['fisioterapeuta',   -4, true,  null,    null,    'retribuida'],
            ['manicurista',       8, false, '17:00', '19:00', 'injustificada'],
            ['rrhh',              3, true,  null,    null,    'justificada'],
        ];

        foreach ($absences as [$role, $offset, $isFullDay, $start, $end, $typeName]) {
            $workerId = User::role($role)->where('center_id', $centerId)->value('id');
            $typeId = $absenceTypes[$typeName] ?? null;

            if ($workerId === null || $typeId === null) {
                continue;
            }

            $date = $this->toWeekday($today->addDays($offset))->toDateString();

            DB::table('worker_absences')->updateOrInsert(
                [
                    'center_id' => $centerId,
                    'worker_id' => $workerId,
                    'date' => $date,
                    'start_time' => $isFullDay ? null : $start.':00',
                    'end_time' => $isFullDay ? null : $end.':00',
                ],
                [
                    'is_full_day' => $isFullDay,
                    'reason' => $isFullDay ? 'Ausencia de jornada completa' : 'Ausencia parcial',
                    'absence_type_id' => $typeId,
                    'notes' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    //evita sembrar ausencias en fin de semana (no se verian en el cuadrante L-V)
    private function toWeekday(CarbonImmutable $date): CarbonImmutable
    {
        if ($date->isoWeekday() === 6) {
            return $date->subDay();
        }

        if ($date->isoWeekday() === 7) {
            return $date->addDay();
        }

        return $date;
    }
}
