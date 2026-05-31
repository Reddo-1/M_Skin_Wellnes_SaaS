<?php

namespace Database\Seeders;

use App\Models\{Center, User};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorkerScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $centerId = Center::query()->where('slug', 'demo')->value('id');

        if ($centerId === null) {
            return;
        }

        $slotByName = DB::table('time_slots')
            ->where('center_id', $centerId)
            ->pluck('id', 'name')
            ->all();

        //cada rol trabaja en su franja
        $roleSlots = [
            'administrador'    => 'Completo',
            'recepcionista'    => 'Completo',
            'rrhh'             => 'Mañana',
            'diagnosticador'   => 'Mañana',
            'dermo_esteticien' => 'Mañana',
            'fisioterapeuta'   => 'Tarde',
            'manicurista'      => 'Tarde',
        ];

        //arranca antes del rango sembrado (hoy-14) para que la jornada cubra todo el cuadrante
        $startDate = now()->subDays(40)->toDateString();

        foreach ($roleSlots as $role => $slotName) {
            $slotId = $slotByName[$slotName] ?? null;

            if ($slotId === null) {
                continue;
            }

            $workerIds = User::role($role)->where('center_id', $centerId)->pluck('id');

            foreach ($workerIds as $workerId) {
                //L-V (1-5)
                for ($weekday = 1; $weekday <= 5; $weekday++) {
                    DB::table('worker_schedules')->updateOrInsert(
                        [
                            'center_id' => $centerId,
                            'worker_id' => $workerId,
                            'weekday' => $weekday,
                            'time_slot_id' => $slotId,
                            'start_date' => $startDate,
                        ],
                        ['end_date' => null]
                    );
                }
            }
        }
    }
}
