<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\ClientConsent;
use App\Models\SessionStatus;
use App\Models\TreatmentConsent;
use App\Models\WorkerSchedule;
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

    //bloquea la cita si el paciente no tiene firmado el consentimiento general del centro
    //o no tiene una valoración vigente apta y consentida para este tratamiento
    private function guardAgainstMissingConsent(int $centerId, int $clientId, int $treatmentId): void
    {
        $hasGeneralConsent = ClientConsent::query()
            ->forCenter($centerId)
            ->where('user_id', $clientId)
            ->where('is_active', true)
            ->exists();

        if (! $hasGeneralConsent) {
            throw ValidationException::withMessages([
                'client_id' => ['El paciente no ha firmado el consentimiento general del centro. Recoge la firma antes de programar la cita.'],
            ]);
        }

        $treatmentConsent = TreatmentConsent::query()
            ->forCenter($centerId)
            ->where('user_id', $clientId)
            ->where('treatment_id', $treatmentId)
            ->where('is_active', true)
            ->first();

        if ($treatmentConsent === null) {
            throw ValidationException::withMessages([
                'treatment_id' => ['El paciente no ha sido valorado para este tratamiento. El diagnosticador debe valorarlo antes de programar la cita.'],
            ]);
        }

        //is_suitable=false NO bloquea: el paciente puede insistir bajo su responsabilidad si consintió igual
        if (! $treatmentConsent->treatment_consent) {
            throw ValidationException::withMessages([
                'treatment_id' => ['El paciente no ha consentido este tratamiento.'],
            ]);
        }
    }

    //comprueba si la cita cae dentro del descanso interno (break_start..break_end) del trabajador
    private function guardAgainstWorkerBreak(int $centerId, CarbonImmutable $startsAt, CarbonImmutable $endsAt, int $workerId): void
    {
        //ISO 8601: 1=lunes ... 7=domingo (coincide con worker_schedules.weekday)
        $weekday = $startsAt->dayOfWeekIso;
        $date = $startsAt->toDateString();
        $apptStart = $startsAt->format('H:i:s');
        $apptEnd = $endsAt->format('H:i:s');

        //busca un schedule vigente del trabajador para ese día cuyo time_slot tenga un break que se solape con la cita
        $hasBreakConflict = WorkerSchedule::query()
            ->forCenter($centerId)
            ->where('worker_id', $workerId)
            ->where('weekday', $weekday)
            ->where('start_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $date);
            })
            ->whereHas('timeSlot', function ($q) use ($apptStart, $apptEnd) {
                $q->whereNotNull('break_start')
                  ->whereNotNull('break_end')
                  ->where('break_start', '<', $apptEnd)
                  ->where('break_end', '>', $apptStart);
            })
            ->exists();

        if ($hasBreakConflict) {
            throw ValidationException::withMessages([
                'starts_at' => ['La cita coincide con el descanso del trabajador.'],
            ]);
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

            $this->guardAgainstWorkerBreak(
                centerId: $centerId,
                startsAt: $startsAt,
                endsAt: $endsAt,
                workerId: (int) $data['worker_id'],
            );

            $this->guardAgainstMissingConsent(
                centerId: $centerId,
                clientId: (int) $data['client_id'],
                treatmentId: (int) $data['treatment_id'],
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

            $this->guardAgainstWorkerBreak(
                centerId: $appointment->center_id,
                startsAt: $startsAt,
                endsAt: $endsAt,
                workerId: (int) ($data['worker_id'] ?? $appointment->worker_id),
            );

            //solo re-validamos consentimiento si cambia el paciente o el tratamiento
            if (array_key_exists('client_id', $data) || array_key_exists('treatment_id', $data)) {
                $this->guardAgainstMissingConsent(
                    centerId: $appointment->center_id,
                    clientId: (int) ($data['client_id'] ?? $appointment->client_id),
                    treatmentId: (int) ($data['treatment_id'] ?? $appointment->treatment_id),
                );
            }

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

    //cambia el estado de la cita; el id del estado llega directamente del FE
    public function changeStatus(Appointment $appointment, int $statusId): Appointment
    {
        return DB::transaction(function () use ($appointment, $statusId) {
            $appointment->status_id = $statusId;

            //leemos el nombre solo para decidir los side effects (cancelada/realizada)
            $statusName = SessionStatus::query()->whereKey($statusId)->value('name');

            if ($statusName === 'cancelada') {
                $appointment->cancelled_at = now();
            }

            if ($statusName === 'realizada' && $appointment->actual_duration_minutes === null) {
                $appointment->actual_duration_minutes = max(0, (int) round(
                    $appointment->starts_at->diffInMinutes(now(), absolute: false)
                ));
            }

            $appointment->save();

            return $appointment->load(['status', 'treatment', 'room', 'machine', 'client', 'worker', 'assistants']);
        });
    }
}
