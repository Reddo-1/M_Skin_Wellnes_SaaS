<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\SessionStatus;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentService
{
    //funcion para validar si algo ya está en uso o ocupado al intentar hacer la inserción.
    private function guardAgainstConflicts(
        int $centerId,
        CarbonImmutable $startsAt,
        CarbonImmutable $endsAt,
        int $workerId,
        int $roomId,
        //si tienen el ? delante es que accepta un entero o puede llegar un null
        ?int $machineId,
        ?int $ignoreId = null,
    ): void {
        $base = Appointment::query()
            ->forCenter($centerId)
            ->notCancelled()
            ->overlapping($startsAt, $endsAt);

        if ($ignoreId !== null) {
            $base->where('id', '!=', $ignoreId);
        }

        $checks = [
            'worker_id'  => ['id' => $workerId,  'message' => 'El trabajador ya tiene una cita en ese rango de horas'],
            'room_id'    => ['id' => $roomId,    'message' => 'La sala ya está en uso ese intervalo de horas.'],
            'machine_id' => ['id' => $machineId, 'message' => 'Esa maquina ya está en uso ese intervalo de horas.'],
        ];

        $errors = [];

        foreach ($checks as $column => $check) {
            if ($check['id'] === null) {
                continue;
            }

            if ((clone $base)->where($column, $check['id'])->exists()) {
                $errors[$column] = $check['message'];
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    public function create(int $centerId, array $data): Appointment
    {
        return DB::transaction(function () use ($centerId, $data) {
            $startsAt = CarbonImmutable::parse($data['starts_at']);
            $endsAt = CarbonImmutable::parse($data['ends_at']);

            $this->guardAgainstConflicts(
                centerId: $centerId,
                startsAt: $startsAt,
                endsAt: $endsAt,
                workerId: (int) $data['worker_id'],
                roomId: (int) $data['room_id'],
                machineId: isset($data['machine_id']) ? (int) $data['machine_id'] : null,
            );

            $appointment = Appointment::create([
                'center_id' => $centerId,
                'treatment_id' => $data['treatment_id'],
                'room_id' => $data['room_id'],
                'client_id' => $data['client_id'],
                'worker_id' => $data['worker_id'],
                'machine_id' => $data['machine_id'] ?? null,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'booking_source' => $data['booking_source'],
                'status_id' => SessionStatus::idFor('pendiente'),
                'reserved_price' => $data['reserved_price'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            //La cantidad de ayudantes que vayan a acudir a la cita
            if (! empty($data['assistant_ids'])) {
                $pivotData = [];
                foreach ($data['assistant_ids'] as $id) {
                    $pivotData[$id] = ['center_id' => $centerId];
                }
                //Hacemos que cree los asistentes que van a acudir a esa cita en la tabla intermedia.
                $appointment->assistants()->sync($pivotData);
            }

            return $appointment->load(['status', 'treatment', 'room', 'machine', 'client', 'worker', 'assistants']);
        });
    }

    public function update(Appointment $appointment, array $data): Appointment
    {
        return DB::transaction(function () use ($appointment, $data) {
            $startsAt = isset($data['starts_at'])
                ? CarbonImmutable::parse($data['starts_at'])
                : CarbonImmutable::instance($appointment->starts_at);

            $endsAt = isset($data['ends_at'])
                ? CarbonImmutable::parse($data['ends_at'])
                : CarbonImmutable::instance($appointment->ends_at);

            $this->guardAgainstConflicts(
                centerId: $appointment->center_id,
                startsAt: $startsAt,
                endsAt: $endsAt,
                workerId: (int) ($data['worker_id'] ?? $appointment->worker_id),
                roomId: (int) ($data['room_id'] ?? $appointment->room_id),
                machineId: array_key_exists('machine_id', $data)? ($data['machine_id'] !== null ? (int) $data['machine_id'] : null): $appointment->machine_id,
                ignoreId: $appointment->id,
            );

            //pone los valores en el objeto en memoria
            $appointment->fill($data);

            $appointment->save();

            //igual que en el create pero aqui modifica o deja igual
            if (array_key_exists('assistant_ids', $data)) {
                $pivotData = [];
                foreach ($data['assistant_ids'] ?? [] as $id) {
                    $pivotData[$id] = ['center_id' => $appointment->center_id];
                }
                $appointment->assistants()->sync($pivotData);
            }

            return $appointment->load(['status', 'treatment', 'room', 'machine', 'client', 'worker', 'assistants']);
        });
    }

    //cambiar el estado de una sesión enviando la sesión y el estado al que quieres cambiar en texto
    public function changeStatus(Appointment $appointment, string $targetStatus): Appointment
    {
        return DB::transaction(function () use ($appointment, $targetStatus) {
            $appointment->status_id = SessionStatus::idFor($targetStatus);

            if ($targetStatus === 'cancelada') {
                $appointment->cancelled_at = now();
            }

            if ($targetStatus === 'realizada' && $appointment->actual_duration_minutes === null) {
                $appointment->actual_duration_minutes = max(0, (int) round(
                    $appointment->starts_at->diffInMinutes(now(), absolute: false)
                ));
            }

            $appointment->save();

            return $appointment->load(['status', 'treatment', 'room', 'machine', 'client', 'worker', 'assistants']);
        });
    }
}
