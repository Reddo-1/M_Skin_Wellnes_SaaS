<?php

namespace Database\Seeders;

use App\Models\{Appointment, Center, Room, Treatment, User};
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AppointmentSeeder extends Seeder
{
    public function run(): void
    {
        //semilla fija -> el seed es reproducible entre ejecuciones
        mt_srand(20260531);

        $centerId = Center::query()->where('slug', 'demo')->value('id');

        if ($centerId === null) {
            return;
        }

        //cada profesional con tratamientos: su franja y su cabina "de casa"
        $config = [
            'dermo_esteticien' => ['Mañana', 'Cabina 2'],
            'fisioterapeuta'   => ['Tarde',  'Cabina 1'],
        ];

        $slots = DB::table('time_slots')->where('center_id', $centerId)->get()->keyBy('name');
        $rooms = Room::query()->where('center_id', $centerId)->pluck('id', 'name');

        $statusIds = [];
        foreach (['confirmada', 'en_curso', 'realizada', 'cancelada', 'no_presentada'] as $code) {
            $statusIds[$code] = (int) config('lookups.session_statuses.'.$code);
        }

        //los 10 clientes son agendables (todos con consent + aptitud)
        $clientIds = User::role('cliente')->where('center_id', $centerId)->orderBy('id')->pluck('id')->all();

        if ($clientIds === []) {
            return;
        }

        $workers = [];
        foreach ($config as $role => [$slotName, $roomName]) {
            $workerId = User::role($role)->where('center_id', $centerId)->value('id');
            $slot = $slots[$slotName] ?? null;

            if ($workerId === null || $slot === null) {
                continue;
            }

            $treatments = Treatment::query()
                ->where('center_id', $centerId)
                ->where('is_active', true)
                ->whereHas('authorizedRoles', fn ($q) => $q->where('name', $role))
                ->with('machines')
                ->get()
                ->all();

            if ($treatments === []) {
                continue;
            }

            $workers[] = [
                'id' => $workerId,
                'room_id' => $rooms[$roomName] ?? null,
                'win_start' => $this->toMinutes($slot->start_time),
                'win_end' => $this->toMinutes($slot->end_time),
                'break_start' => $slot->break_start ? $this->toMinutes($slot->break_start) : null,
                'break_end' => $slot->break_end ? $this->toMinutes($slot->break_end) : null,
                'treatments' => $treatments,
            ];
        }

        $now = CarbonImmutable::now('Europe/Madrid');
        $today = CarbonImmutable::today('Europe/Madrid');

        for ($d = $today->subDays(7); $d->lte($today->addDays(7)); $d = $d->addDay()) {
            //L-V; los sabados se cubren con las disponibilidades extra
            if ($d->isoWeekday() > 5) {
                continue;
            }

            foreach ($workers as $worker) {
                $cursor = $worker['win_start'];
                $target = mt_rand(1, 3);
                $placed = 0;

                while ($placed < $target && $cursor < $worker['win_end']) {
                    $treatment = $worker['treatments'][mt_rand(0, count($worker['treatments']) - 1)];
                    $duration = (int) $treatment->duration_minutes;
                    $apptEnd = $cursor + $duration;

                    //si la cita pisa el descanso, salta al final del descanso
                    if ($worker['break_start'] !== null && $cursor < $worker['break_end'] && $apptEnd > $worker['break_start']) {
                        $cursor = $worker['break_end'];
                        continue;
                    }

                    if ($apptEnd > $worker['win_end']) {
                        break;
                    }

                    $startsAt = $d->setTime(intdiv($cursor, 60), $cursor % 60);
                    $endsAt = $startsAt->addMinutes($duration);
                    $status = $this->statusFor($startsAt, $endsAt, $now);

                    Appointment::updateOrCreate(
                        [
                            'center_id' => $centerId,
                            'worker_id' => $worker['id'],
                            'starts_at' => $startsAt,
                        ],
                        [
                            'treatment_id' => $treatment->id,
                            'room_id' => $worker['room_id'],
                            'client_id' => $clientIds[mt_rand(0, count($clientIds) - 1)],
                            'machine_id' => $treatment->machines->first()?->id,
                            'ends_at' => $endsAt,
                            'booking_source' => 'panel',
                            'status_id' => $statusIds[$status],
                            'reserved_price' => $treatment->price,
                            'cancelled_at' => $status === 'cancelada' ? $startsAt : null,
                            'actual_duration_minutes' => $status === 'realizada' ? $duration : null,
                            'notes' => null,
                        ]
                    );

                    $placed++;
                    $cursor = $apptEnd + mt_rand(0, 2) * 15;
                }
            }
        }
    }

    private function toMinutes(string $time): int
    {
        [$h, $m] = explode(':', $time);

        return (int) $h * 60 + (int) $m;
    }

    private function statusFor(CarbonImmutable $start, CarbonImmutable $end, CarbonImmutable $now): string
    {
        if ($end->lte($now)) {
            $r = mt_rand(1, 100);

            return $r <= 70 ? 'realizada' : ($r <= 85 ? 'no_presentada' : 'cancelada');
        }

        if ($start->lte($now)) {
            return 'en_curso';
        }

        $r = mt_rand(1, 100);

        return $r <= 90 ? 'confirmada' : 'cancelada';
    }
}
