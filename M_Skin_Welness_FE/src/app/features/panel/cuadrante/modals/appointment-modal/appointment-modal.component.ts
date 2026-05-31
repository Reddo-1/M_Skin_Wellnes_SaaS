import { Component, computed, effect, inject, input, output, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { AppointmentSummary } from '../../../../../core/models/appointment.model';
import { Machine } from '../../../../../core/models/machine.model';
import { Room } from '../../../../../core/models/room.model';
import { Treatment } from '../../../../../core/models/treatment.model';
import { User } from '../../../../../core/models/user.model';
import { LookupService } from '../../../../../core/services/lookup.service';
import { hasFieldError, hasValidationError } from '../../../../../core/utils/form.util';
import { DatePickerComponent } from '../../../../../shared/ui/date-picker/date-picker.component';
import { InputComponent } from '../../../../../shared/ui/input/input.component';
import { ModalComponent } from '../../../../../shared/ui/modal/modal.component';
import { MultiSelectComponent } from '../../../../../shared/ui/multi-select/multi-select.component';
import { SelectComponent, SelectOption } from '../../../../../shared/ui/select/select.component';
import { TextareaComponent } from '../../../../../shared/ui/textarea/textarea.component';
import { TimePickerComponent } from '../../../../../shared/ui/time-picker/time-picker.component';

export interface AppointmentFormValue {
  client_id: number;
  treatment_id: number;
  worker_id: number;
  room_id: number;
  machine_id: number | null;
  starts_at: string;
  ends_at: string;
  status_id: number;
  reserved_price: number | null;
  notes: string | null;
  assistant_ids: number[];
}

export interface AppointmentPrefill {
  worker_id: number;
  date: string;
  time: string;
}

type AppointmentField =
  | 'client_id'
  | 'treatment_id'
  | 'worker_id'
  | 'room_id'
  | 'date'
  | 'time'
  | 'status_id'
  | 'notes';

interface StatusAction {
  status_id: number;
  label: string;
  tone: 'success' | 'danger' | 'neutral';
}

const pad = (value: number): string => value.toString().padStart(2, '0');

@Component({
  selector: 'app-appointment-modal',
  standalone: true,
  imports: [
    ReactiveFormsModule,
    ModalComponent,
    SelectComponent,
    MultiSelectComponent,
    DatePickerComponent,
    TimePickerComponent,
    InputComponent,
    TextareaComponent,
  ],
  templateUrl: './appointment-modal.component.html',
})
export class AppointmentModalComponent {
  readonly isOpen = input.required<boolean>();
  readonly appointment = input<AppointmentSummary | null>(null);
  readonly prefill = input<AppointmentPrefill | null>(null);
  readonly submitting = input.required<boolean>();
  readonly clients = input<User[]>([]);
  readonly workers = input<User[]>([]);
  readonly treatments = input<Treatment[]>([]);
  readonly rooms = input<Room[]>([]);
  readonly machines = input<Machine[]>([]);
  readonly canManage = input<boolean>(false);

  readonly close = output<void>();
  readonly formSubmit = output<AppointmentFormValue>();
  readonly statusChange = output<number>();
  readonly remove = output<void>();

  private readonly fb = inject(FormBuilder);
  protected readonly lookup = inject(LookupService);

  protected readonly isEdit = computed(() => this.appointment() !== null);

  private readonly selectedWorkerId = signal<string>('');
  private readonly selectedTreatmentId = signal<string>('');

  protected readonly clientOptions = computed<SelectOption[]>(() =>
    this.clients().map((client) => ({ value: String(client.id), label: client.name })),
  );

  protected readonly treatmentOptions = computed<SelectOption[]>(() =>
    this.treatments().map((treatment) => ({
      value: String(treatment.id),
      label: `${treatment.name} · ${treatment.duration_minutes} min`,
    })),
  );

  protected readonly workerOptions = computed<SelectOption[]>(() =>
    this.workers().map((worker) => ({ value: String(worker.id), label: worker.name })),
  );

  protected readonly roomOptions = computed<SelectOption[]>(() =>
    this.rooms().map((room) => ({ value: String(room.id), label: room.name })),
  );

  protected readonly machineOptions = computed<SelectOption[]>(() => [
    { value: '', label: 'Sin máquina' },
    ...this.machines().map((machine) => ({ value: String(machine.id), label: machine.name })),
  ]);

  protected readonly assistantOptions = computed(() =>
    this.workers()
      .filter((worker) => String(worker.id) !== this.selectedWorkerId())
      .map((worker) => ({ id: worker.id, name: worker.name })),
  );

  //duracion del tratamiento elegido (reactiva al cambio de tratamiento)
  protected readonly selectedDuration = computed<number | null>(() => {
    const treatment = this.treatments().find(
      (item) => String(item.id) === this.selectedTreatmentId(),
    );
    return treatment ? treatment.duration_minutes : null;
  });

  //estados con los que se puede dar de alta una cita desde el cuadrante
  protected readonly createStatusOptions = computed<SelectOption[]>(() =>
    this.lookup
      .sessionStatuses()
      .filter((status) => ['pendiente', 'confirmada'].includes(this.normalize(status.name)))
      .map((status) => ({ value: String(status.id), label: status.name })),
  );

  //acciones de estado segun el estado actual (iniciar/finalizar viven en el mapa)
  protected readonly statusActions = computed<StatusAction[]>(() => {
    const current = this.appointment();
    if (current?.status == null) return [];
    const targets: { code: string; label: string; tone: StatusAction['tone'] }[] = [];
    switch (this.normalize(current.status.name)) {
      case 'pendiente':
        targets.push({ code: 'confirmada', label: 'Confirmar', tone: 'success' });
        targets.push({ code: 'no_presentada', label: 'No presentada', tone: 'neutral' });
        targets.push({ code: 'cancelada', label: 'Cancelar', tone: 'danger' });
        break;
      case 'confirmada':
        targets.push({ code: 'no_presentada', label: 'No presentada', tone: 'neutral' });
        targets.push({ code: 'cancelada', label: 'Cancelar', tone: 'danger' });
        break;
    }
    return targets
      .map((target) => {
        const statusId = this.statusIdFor(target.code);
        return statusId === null
          ? null
          : { status_id: statusId, label: target.label, tone: target.tone };
      })
      .filter((action): action is StatusAction => action !== null);
  });

  protected readonly form = this.fb.nonNullable.group({
    client_id: ['', Validators.required],
    treatment_id: ['', Validators.required],
    worker_id: ['', Validators.required],
    room_id: ['', Validators.required],
    machine_id: [''],
    date: ['', Validators.required],
    time: ['', Validators.required],
    status_id: ['', Validators.required],
    reserved_price: this.fb.control<number | null>(null),
    notes: ['', Validators.maxLength(5000)],
    assistant_ids: this.fb.nonNullable.control<number[]>([]),
  });

  constructor() {
    effect(() => {
      if (!this.isOpen()) return;
      const current = this.appointment();
      const prefill = this.prefill();
      if (current !== null) {
        const { date, time } = this.splitIso(current.starts_at);
        this.form.reset({
          client_id: current.client ? String(current.client.id) : '',
          treatment_id: current.treatment ? String(current.treatment.id) : '',
          worker_id: current.worker ? String(current.worker.id) : '',
          room_id: current.room ? String(current.room.id) : '',
          machine_id: current.machine ? String(current.machine.id) : '',
          date,
          time,
          status_id: current.status ? String(current.status.id) : '',
          reserved_price: current.reserved_price !== null ? Number(current.reserved_price) : null,
          notes: current.notes ?? '',
          assistant_ids: current.assistants?.map((assistant) => assistant.id) ?? [],
        });
        this.selectedWorkerId.set(current.worker ? String(current.worker.id) : '');
        this.selectedTreatmentId.set(current.treatment ? String(current.treatment.id) : '');
      } else {
        const confirmada =
          this.createStatusOptions().find((option) => this.optionIsConfirmada(option))?.value ??
          this.createStatusOptions()[0]?.value ??
          '';
        this.form.reset({
          client_id: '',
          treatment_id: '',
          worker_id: prefill ? String(prefill.worker_id) : '',
          room_id: '',
          machine_id: '',
          date: prefill?.date ?? this.today(),
          time: prefill?.time ?? '',
          status_id: confirmada,
          reserved_price: null,
          notes: '',
          assistant_ids: [],
        });
        this.selectedWorkerId.set(prefill ? String(prefill.worker_id) : '');
        this.selectedTreatmentId.set('');
      }
    });
  }

  protected onWorkerChange(value: string): void {
    this.form.controls.worker_id.setValue(value);
    this.selectedWorkerId.set(value);
    const numeric = Number(value);
    this.form.controls.assistant_ids.setValue(
      this.form.controls.assistant_ids.value.filter((id) => id !== numeric),
    );
  }

  protected onTreatmentChange(value: string): void {
    this.form.controls.treatment_id.setValue(value);
    this.selectedTreatmentId.set(value);
    //prefijar el precio reservado con la tarifa del tratamiento si esta vacio
    if (this.form.controls.reserved_price.value === null) {
      const treatment = this.treatments().find((item) => String(item.id) === value);
      if (treatment) {
        this.form.controls.reserved_price.setValue(Number(treatment.price));
      }
    }
  }

  protected onSubmit(): void {
    if (this.submitting()) return;
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    const raw = this.form.getRawValue();
    const treatment = this.treatments().find((item) => String(item.id) === raw.treatment_id);
    const duration = treatment?.duration_minutes ?? 0;
    const start = new Date(`${raw.date}T${raw.time}:00`);
    const end = new Date(start.getTime() + duration * 60000);

    this.formSubmit.emit({
      client_id: Number(raw.client_id),
      treatment_id: Number(raw.treatment_id),
      worker_id: Number(raw.worker_id),
      room_id: Number(raw.room_id),
      machine_id: raw.machine_id === '' ? null : Number(raw.machine_id),
      starts_at: this.toOffsetIso(start),
      ends_at: this.toOffsetIso(end),
      status_id: Number(raw.status_id),
      reserved_price: raw.reserved_price,
      notes: raw.notes.trim() === '' ? null : raw.notes,
      assistant_ids: raw.assistant_ids,
    });
  }

  protected hasFieldError(field: AppointmentField): boolean {
    return hasFieldError(this.form.controls[field]);
  }

  protected hasValidationError(field: AppointmentField, key: string): boolean {
    return hasValidationError(this.form.controls[field], key);
  }

  private optionIsConfirmada(option: SelectOption): boolean {
    const status = this.lookup.sessionStatuses().find((item) => String(item.id) === option.value);
    return status ? this.normalize(status.name) === 'confirmada' : false;
  }

  private statusIdFor(code: string): number | null {
    const status = this.lookup
      .sessionStatuses()
      .find((item) => this.normalize(item.name) === code);
    return status ? status.id : null;
  }

  private normalize(name: string): string {
    return name.toLowerCase().trim().replace(/\s+/g, '_');
  }

  private splitIso(iso: string | null): { date: string; time: string } {
    if (iso === null) return { date: this.today(), time: '' };
    const parsed = new Date(iso);
    const date = `${parsed.getFullYear()}-${pad(parsed.getMonth() + 1)}-${pad(parsed.getDate())}`;
    return { date, time: `${pad(parsed.getHours())}:${pad(parsed.getMinutes())}` };
  }

  private toOffsetIso(value: Date): string {
    const offsetMinutes = -value.getTimezoneOffset();
    const sign = offsetMinutes >= 0 ? '+' : '-';
    const abs = Math.abs(offsetMinutes);
    const stamp = `${value.getFullYear()}-${pad(value.getMonth() + 1)}-${pad(value.getDate())}T${pad(value.getHours())}:${pad(value.getMinutes())}:00`;
    return `${stamp}${sign}${pad(Math.floor(abs / 60))}:${pad(abs % 60)}`;
  }

  private today(): string {
    const now = new Date();
    return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
  }
}
