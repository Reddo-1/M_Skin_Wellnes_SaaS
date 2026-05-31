<?php

namespace Database\Seeders;

use App\Models\{Appointment, Center, Machine, Room, Treatment, User};
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class AppointmentSeeder extends Seeder
{
    public function run(): void
    {
        $centerId = Center::query()->where('slug', 'demo')->value('id');

        if ($centerId === null) {
            return;
        }

        $workers = [
            'diagno' => User::query()->where('email', 'diagno@demo.test')->value('id'),
            'dermo'  => User::query()->where('email', 'dermo@demo.test')->value('id'),
            'fisio'  => User::query()->where('email', 'fisio@demo.test')->value('id'),
            'mani'   => User::query()->where('email', 'mani@demo.test')->value('id'),
        ];

        $clients = User::query()
            ->where('center_id', $centerId)
            ->whereIn('email', [
                'cliente1@demo.test', 'cliente2@demo.test', 'cliente3@demo.test', 'cliente4@demo.test',
                'cliente5@demo.test', 'cliente6@demo.test', 'cliente7@demo.test', 'cliente8@demo.test',
            ])
            ->pluck('id', 'email')
            ->all();

        $treatments = Treatment::query()
            ->where('center_id', $centerId)
            ->pluck('id', 'name')
            ->all();

        $rooms = Room::query()->where('center_id', $centerId)->pluck('id', 'name')->all();
        $machineRoom = $rooms['Sala Maquinaria'] ?? null;
        $facialesRoom = $rooms['Sala Faciales'] ?? null;
        $diagnoRoom = $rooms['Sala Diagnóstico'] ?? null;

        $machines = Machine::query()->where('center_id', $centerId)->pluck('id', 'name')->all();
        $rfMachine    = $machines['Radiofrecuencia RF-100'] ?? null;
        $laserMachine = $machines['Láser diodo LD-200'] ?? null;
        $pressureMachine = $machines['Presoterapia móvil'] ?? null;

        //arranca hoy en la zona del centro para que la hora de pared (09:00...) cuadre con el overlay
        //de jornada del cuadrante y no se desfase al renderizar en el navegador (dia 0 = hoy)
        $base = CarbonImmutable::today('Europe/Madrid')->setTime(9, 0);

        //15 citas: dia, hora, worker, cliente_email, treatment, room, machine?, status
        $rows = [
            [0,  9, 'diagno', 'cliente1@demo.test', 'Diagnóstico inicial',                $diagnoRoom,   null,             'realizada'],
            [0, 10, 'dermo',  'cliente2@demo.test', 'Limpieza facial profunda',           $facialesRoom, null,             'realizada'],
            [0, 12, 'fisio',  'cliente3@demo.test', 'Radiofrecuencia corporal',           $machineRoom,  $rfMachine,       'realizada'],
            [1,  9, 'diagno', 'cliente4@demo.test', 'Diagnóstico inicial',                $diagnoRoom,   null,             'cancelada'],
            [1, 11, 'dermo',  'cliente5@demo.test', 'Tratamiento antiedad',               $facialesRoom, null,             'confirmada'],
            [1, 17, 'mani',   'cliente6@demo.test', 'Manicura completa',                  $facialesRoom, null,             'pendiente'],
            [2,  9, 'dermo',  'cliente7@demo.test', 'Limpieza facial profunda',           $facialesRoom, null,             'confirmada'],
            [2, 10, 'fisio',  'cliente8@demo.test', 'Depilación láser piernas',           $machineRoom,  $laserMachine,    'confirmada'],
            [2, 12, 'fisio',  'cliente1@demo.test', 'Drenaje linfático con presoterapia', $machineRoom,  $pressureMachine, 'pendiente'],
            [3,  9, 'diagno', 'cliente2@demo.test', 'Diagnóstico inicial',                $diagnoRoom,   null,             'pendiente'],
            [3, 10, 'dermo',  'cliente3@demo.test', 'Tratamiento antiedad',               $facialesRoom, null,             'pendiente'],
            [3, 17, 'mani',   'cliente4@demo.test', 'Manicura completa',                  $facialesRoom, null,             'pendiente'],
            [4,  9, 'fisio',  'cliente5@demo.test', 'Radiofrecuencia corporal',           $machineRoom,  $rfMachine,       'pendiente'],
            [4, 10, 'fisio',  'cliente6@demo.test', 'Depilación láser piernas',           $machineRoom,  $laserMachine,    'pendiente'],
            [4, 12, 'dermo',  'cliente7@demo.test', 'Limpieza facial profunda',           $facialesRoom, null,             'pendiente'],
        ];

        foreach ($rows as [$dayOffset, $hour, $workerKey, $clientEmail, $treatmentName, $roomId, $machineId, $statusName]) {
            $workerId = $workers[$workerKey] ?? null;
            $clientId = $clients[$clientEmail] ?? null;
            $treatmentId = $treatments[$treatmentName] ?? null;

            if ($workerId === null || $clientId === null || $treatmentId === null || $roomId === null) {
                continue;
            }

            $treatment = Treatment::query()->find($treatmentId);
            $startsAt = $base->addDays($dayOffset)->setTime($hour, 0);
            $endsAt = $startsAt->addMinutes($treatment->duration_minutes);

            $appointment = Appointment::updateOrCreate(
                [
                    'center_id' => $centerId,
                    'worker_id' => $workerId,
                    'starts_at' => $startsAt,
                ],
                [
                    'treatment_id' => $treatmentId,
                    'room_id' => $roomId,
                    'client_id' => $clientId,
                    'machine_id' => $machineId,
                    'ends_at' => $endsAt,
                    'booking_source' => 'staff',
                    'status_id' => (int) config('lookups.session_statuses.'.$statusName),
                    'reserved_price' => $treatment->price,
                    'cancelled_at' => $statusName === 'cancelada' ? $startsAt : null,
                    'actual_duration_minutes' => $statusName === 'realizada' ? $treatment->duration_minutes : null,
                    'notes' => null,
                ]
            );
        }
    }
}
