<?php

namespace Database\Seeders;

use App\Models\Center;
use App\Models\User;
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

        $morningSlotId  = DB::table('time_slots')->where('center_id', $centerId)->where('name', 'Mañana')->value('id');
        $afternoonSlotId = DB::table('time_slots')->where('center_id', $centerId)->where('name', 'Tarde')->value('id');
        $fullSlotId     = DB::table('time_slots')->where('center_id', $centerId)->where('name', 'Completo')->value('id');

        $assignments = [
            ['admin@demo.test',     $fullSlotId],
            ['recepcion@demo.test', $fullSlotId],
            ['rrhh@demo.test',      $morningSlotId],
            ['diagno@demo.test',    $morningSlotId],
            ['dermo@demo.test',     $morningSlotId],
            ['fisio@demo.test',     $afternoonSlotId],
            ['mani@demo.test',      $afternoonSlotId],
        ];

        $startDate = now()->startOfMonth()->toDateString();

        foreach ($assignments as [$email, $slotId]) {
            if ($slotId === null) {
                continue;
            }

            $workerId = User::query()
                ->where('email', $email)
                ->where('center_id', $centerId)
                ->value('id');

            if ($workerId === null) {
                continue;
            }

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
                    [
                        'end_date' => null,
                    ]
                );
            }
        }
    }
}
