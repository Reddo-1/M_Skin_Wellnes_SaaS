<?php

namespace App\Services;

use App\Models\{Appointment, ClientConsent, Treatment, TreatmentConsent, WorkerSchedule};
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentService
{
    public function __construct(private readonly AppointmentProductService $appointmentProducts)
    {
    }

    private function guardAgainstConflicts(
        int $centerId,
        CarbonImmutable $startsAt,
        CarbonImmutable $endsAt,
        int $clientId,
        int $workerId,
        int $roomId,
        ?int $machineId,
        array $assistantIds = [],
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
            'client_id'  => ['id' => $clientId,  'message' => 'El paciente ya tiene una cita en ese rango de horas.'],
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

        //un ayudante no puede estar ocupado en ese rango, ni como profesional ni como ayudante de otra cita
        if ($assistantIds !== []) {
            $assistantBusy = (clone $base)
                ->where(function ($q) use ($assistantIds) {
                    $q->whereIn('worker_id', $assistantIds)
                      ->orWhereHas('assistants', fn ($a) => $a->whereIn('users.id', $assistantIds));
                })
                ->exists();

            if ($assistantBusy) {
                $errors['assistant_ids'] = 'Alguno de los ayudantes ya tiene una cita en ese rango de horas.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    //si el tratamiento usa máquina(s), la cita debe reservar una de las compatibles (y libre, que valida guardAgainstConflicts)
    private function guardAgainstMachineRequirement(int $centerId, int $treatmentId, ?int $machineId): void
    {
        $compatibleIds = DB::table('machine_treatment')
            ->where('center_id', $centerId)
            ->where('treatment_id', $treatmentId)
            ->pluck('machine_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($compatibleIds === []) {
            return;
        }

        if ($machineId === null) {
            throw ValidationException::withMessages([
                'machine_id' => ['Este tratamiento requiere una máquina.'],
            ]);
        }

        if (! in_array($machineId, $compatibleIds, true)) {
            throw ValidationException::withMessages([
                'machine_id' => ['La máquina seleccionada no es válida para este tratamiento.'],
            ]);
        }
    }

    //bloquea la cita si falta el consentimiento general del centro o una valoración vigente apta y consentida para el tratamiento
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

        //el wizard crea el registro al consentir; si no existe o no consintió, no se agenda
        if ($treatmentConsent === null || ! $treatmentConsent->treatment_consent) {
            throw ValidationException::withMessages([
                'treatment_id' => ['El paciente no ha consentido este tratamiento.'],
            ]);
        }

        //aptitud: null = sin valorar, false = no apto; ambos bloquean (solo se agenda si el diagnosticador lo marcó apto)
        if ($treatmentConsent->is_suitable === null) {
            throw ValidationException::withMessages([
                'treatment_id' => ['El paciente está pendiente de valoración clínica para este tratamiento.'],
            ]);
        }

        if ($treatmentConsent->is_suitable === false) {
            throw ValidationException::withMessages([
                'treatment_id' => ['El paciente no es apto para este tratamiento según la valoración clínica.'],
            ]);
        }
    }

    private function guardAgainstWorkerBreak(int $centerId, CarbonImmutable $startsAt, CarbonImmutable $endsAt, int $workerId): void
    {
        //ISO 8601: 1=lunes ... 7=domingo (coincide con worker_schedules.weekday)
        $weekday = $startsAt->dayOfWeekIso;
        $date = $startsAt->toDateString();
        $apptStart = $startsAt->format('H:i:s');
        $apptEnd = $endsAt->format('H:i:s');

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

            $machineId = isset($data['machine_id']) ? (int) $data['machine_id'] : null;

            $this->guardAgainstMachineRequirement(
                centerId: $centerId,
                treatmentId: (int) $data['treatment_id'],
                machineId: $machineId,
            );

            $this->guardAgainstConflicts(
                centerId: $centerId,
                startsAt: $startsAt,
                endsAt: $endsAt,
                clientId: (int) $data['client_id'],
                workerId: (int) $data['worker_id'],
                roomId: (int) $data['room_id'],
                machineId: $machineId,
                assistantIds: array_map('intval', $data['assistant_ids'] ?? []),
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

            //precio reservado = tarifa del tratamiento en este momento (historico, congela el importe)
            $reservedPrice = Treatment::forCenter($centerId)->whereKey((int) $data['treatment_id'])->value('price');

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
                //las citas nacen confirmadas (el estado pendiente ya no existe)
                'status_id' => (int) config('lookups.session_statuses.confirmada'),
                'reserved_price' => $reservedPrice,
                'notes' => $data['notes'] ?? null,
            ]);

            if (! empty($data['assistant_ids'])) {
                $pivotData = [];
                foreach ($data['assistant_ids'] as $id) {
                    $pivotData[$id] = ['center_id' => $centerId];
                }
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

            $machineId = array_key_exists('machine_id', $data)
                ? ($data['machine_id'] !== null ? (int) $data['machine_id'] : null)
                : $appointment->machine_id;

            //el tratamiento de una cita es inmutable: su requisito de máquina se valida contra el tratamiento ya guardado
            $this->guardAgainstMachineRequirement(
                centerId: $appointment->center_id,
                treatmentId: (int) $appointment->treatment_id,
                machineId: $machineId,
            );

            $assistantIds = array_key_exists('assistant_ids', $data)
                ? array_map('intval', $data['assistant_ids'] ?? [])
                : $appointment->assistants()->pluck('users.id')->map(fn ($id) => (int) $id)->all();

            $this->guardAgainstConflicts(
                centerId: $appointment->center_id,
                startsAt: $startsAt,
                endsAt: $endsAt,
                clientId: (int) $appointment->client_id,
                workerId: (int) ($data['worker_id'] ?? $appointment->worker_id),
                roomId: (int) ($data['room_id'] ?? $appointment->room_id),
                machineId: $machineId,
                assistantIds: $assistantIds,
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

            $appointment->fill($data);

            $appointment->save();

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

    //al cerrar la sesion (realizada) pueden llegar los productos consumidos para adjuntarlos y descontar stock
    public function changeStatus(Appointment $appointment, int $statusId, int $actorId, array $products = []): Appointment
    {
        return DB::transaction(function () use ($appointment, $statusId, $actorId, $products) {
            $cancelledId = (int) config('lookups.session_statuses.cancelada');
            $doneId = (int) config('lookups.session_statuses.realizada');
            $previousStatusId = (int) $appointment->status_id;

            $appointment->status_id = $statusId;

            if ($statusId === $cancelledId) {
                $appointment->cancelled_at = now();
            }

            if ($statusId === $doneId && $appointment->actual_duration_minutes === null) {
                $appointment->actual_duration_minutes = max(0, (int) round(
                    $appointment->starts_at->diffInMinutes(now(), absolute: false)
                ));
            }

            $appointment->save();

            //al pasar A 'realizada' por primera vez: adjunta los productos consumidos y descuenta su stock
            if ($statusId === $doneId && $previousStatusId !== $doneId) {
                foreach ($products as $line) {
                    $this->appointmentProducts->attach($appointment, (int) $line['product_id'], (float) $line['quantity']);
                }
                $this->appointmentProducts->applyStockConsumption($appointment, $actorId);
            }

            return $appointment->load(['status', 'treatment', 'room', 'machine', 'client', 'worker', 'assistants']);
        });
    }
}
