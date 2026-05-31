<?php

namespace Database\Seeders;

use App\Models\{Center, User};
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorkerExtraAvailabilitySeeder extends Seeder
{
    public function run(): void
    {
        $centerId = Center::query()->where('slug', 'demo')->value('id');

        if ($centerId === null) {
            return;
        }

        $today = CarbonImmutable::today('Europe/Madrid');

        //sabados dentro del rango [hoy-14, hoy+14]
        $saturdays = [];
        for ($d = $today->subDays(14); $d->lte($today->addDays(14)); $d = $d->addDay()) {
            if ($d->isoWeekday() === 6) {
                $saturdays[] = $d->toDateString();
            }
        }

        //rol, indice de sabado, inicio, fin (aperturas puntuales de fin de semana)
        $extras = [
            ['dermo_esteticien', 0, '10:00', '14:00'],
            ['fisioterapeuta',   0, '10:00', '14:00'],
            ['manicurista',      1, '11:00', '15:00'],
            ['dermo_esteticien', 1, '10:00', '13:00'],
        ];

        foreach ($extras as [$role, $saturdayIndex, $start, $end]) {
            $date = $saturdays[$saturdayIndex] ?? null;
            $workerId = User::role($role)->where('center_id', $centerId)->value('id');

            if ($date === null || $workerId === null) {
                continue;
            }

            DB::table('worker_extra_availabilities')->updateOrInsert(
                [
                    'center_id' => $centerId,
                    'worker_id' => $workerId,
                    'date' => $date,
                    'start_time' => $start.':00',
                    'end_time' => $end.':00',
                ],
                [
                    'reason' => 'Apertura puntual de sábado',
                    'created_at' => now(),
                ]
            );
        }
    }
}
