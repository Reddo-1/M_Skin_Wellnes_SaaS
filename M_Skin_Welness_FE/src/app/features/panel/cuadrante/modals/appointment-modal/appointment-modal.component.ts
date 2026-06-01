import { Component, computed, effect, inject, input, output, signal } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { AppointmentSummary } from '../../../../../core/models/appointment.model';
import { Machine } from '../../../../../core/models/machine.model';
import { Room } from '../../../../../core/models/room.model';
import { Treatment } from '../../../../../core/models/treatment.model';
import { User, UserRole } from '../../../../../core/models/user.model';
import { WorkerSchedule } from '../../../../../core/models/worker-schedule.model';
import { WorkerAbsence } from '../../../../../core/models/worker-absence.model';
import { WorkerExtraAvailability } from '../../../../../core/models/worker-extra-availability.model';
import { AppointmentService } from '../../../../../core/services/appointment.service';
import { ClientService } from '../../../../../core/services/client.service';
import { ConsentService, TreatmentConsentSummary } from '../../../../../core/services/consent.service';
import { LookupService } from '../../../../../core/services/lookup.service';
import { WorkerAbsenceService } from '../../../../../core/services/worker-absence.service';
import { WorkerExtraAvailabilityService } from '../../../../../core/services/worker-extra-availability.service';
import { WorkerScheduleService } from '../../../../../core/services/worker-schedule.service';
import { hasFieldError, hasValidationError } from '../../../../../core/utils/form.util';
import { formatLocalDate, pad, toOffsetIso } from '../../../../../core/utils/datetime.util';
import { DatePickerComponent } from '../../../../../shared/ui/date-picker/date-picker.component';
import { InputComponent } from '../../../../../shared/ui/input/input.component';
import { ModalComponent } from '../../../../../shared/ui/modal/modal.component';
import { MultiSelectComponent } from '../../../../../shared/ui/multi-select/multi-select.component';
import {
  SearchSelectComponent,
  SearchSelectOption,
} from '../../../../../shared/ui/search-select/search-select.component';
import { SelectComponent, SelectOption } from '../../../../../shared/ui/select/select.component';
import { TextareaComponent } from '../../../../../shared/ui/textarea/textarea.component';

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

//roles que realizan tratamientos: un usuario es agendable si tiene al menos uno
const PRACTITIONER_ROLES: UserRole[] = [
  'diagnosticador',
  'dermo_esteticien',
  'fisioterapeuta',
  'manicurista',
];

//granularidad de los inicios de cita ofrecidos
const SLOT_STEP_MINUTES = 15;

@Component({
  selector: 'app-appointment-modal',
  standalone: true,
  imports: [
    ReactiveFormsModule,
    ModalComponent,
    SelectComponent,
    SearchSelectComponent,
    MultiSelectComponent,
    DatePickerComponent,
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
  private readonly clientService = inject(ClientService);
  private readonly consentService = inject(ConsentService);
  private readonly appointmentService = inject(AppointmentService);
  private readonly scheduleService = inject(WorkerScheduleService);
  private readonly absenceService = inject(WorkerAbsenceService);
  private readonly extraService = inject(WorkerExtraAvailabilityService);

  protected readonly isEdit = computed(() => this.appointment() !== null);
  protected readonly isMobile = signal(false);

  //buscador de paciente (servidor)
  protected readonly clientResults = signal<SearchSelectOption[]>([]);
  protected readonly clientLoading = signal(false);
  protected readonly selectedClientLabel = signal<string | null>(null);

  //consentimientos/aptitud del paciente elegido
  private readonly clientConsents = signal<TreatmentConsentSummary[]>([]);
  protected readonly hasGeneralConsent = signal(false);
  protected readonly consentLoading = signal(false);
  private lastConsentClientId = 0;

  //disponibilidad del día (jornadas, ausencias, extras y citas de todos los profesionales)
  private readonly schedules = signal<WorkerSchedule[]>([]);
  private readonly absences = signal<WorkerAbsence[]>([]);
  private readonly extras = signal<WorkerExtraAvailability[]>([]);
  private readonly dayAppointments = signal<AppointmentSummary[]>([]);
  protected readonly availabilityLoading = signal(false);
  private lastBundleDate = '';

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

  //tick reactivo del form; el valor se lee crudo (no opcional) dentro de un computed
  private readonly formChanges = toSignal(this.form.valueChanges, { initialValue: null });
  private readonly formValue = computed(() => {
    this.formChanges();
    return this.form.getRawValue();
  });

  //solo staff con algún rol asistencial puede atender una cita
  private readonly practitioners = computed(() =>
    this.workers().filter((worker) => worker.roles.some((role) => PRACTITIONER_ROLES.includes(role))),
  );

  protected readonly workerOptions = computed<SelectOption[]>(() =>
    this.practitioners().map((worker) => ({ value: String(worker.id), label: worker.name })),
  );

  private readonly selectedWorker = computed<User | null>(
    () => this.workers().find((worker) => String(worker.id) === this.formValue().worker_id) ?? null,
  );

  protected readonly selectedTreatment = computed<Treatment | null>(
    () =>
      this.treatments().find((item) => String(item.id) === this.formValue().treatment_id) ?? null,
  );

  protected readonly selectedDuration = computed<number | null>(
    () => this.selectedTreatment()?.duration_minutes ?? null,
  );

  //tratamientos elegibles: autorizados por el rol del profesional y consentidos+aptos por el paciente
  private readonly eligibleTreatments = computed<Treatment[]>(() => {
    const worker = this.selectedWorker();
    if (worker === null || this.formValue().client_id === '') return [];
    const consentByTreatment = new Map(
      this.clientConsents().map((consent) => [consent.treatment_id, consent]),
    );
    return this.treatments().filter((treatment) => {
      const roleOk = treatment.authorized_roles.some((role) =>
        worker.roles.some((workerRole) => workerRole === role.name),
      );
      const consent = consentByTreatment.get(treatment.id);
      return roleOk && consent?.treatment_consent === true && consent?.is_suitable === true;
    });
  });

  protected readonly treatmentOptions = computed<SelectOption[]>(() => {
    const options = this.eligibleTreatments().map((treatment) => ({
      value: String(treatment.id),
      label: `${treatment.name} · ${treatment.duration_minutes} min`,
    }));
    //en edición, el tratamiento actual debe seguir visible aunque cambien consentimientos
    const current = this.appointment()?.treatment;
    if (current && !options.some((option) => option.value === String(current.id))) {
      options.unshift({
        value: String(current.id),
        label: `${current.name} · ${current.duration_minutes} min`,
      });
    }
    return options;
  });

  protected readonly treatmentHint = computed<string | null>(() => {
    if (this.treatmentDisabled() || this.consentLoading()) return null;
    if (this.eligibleTreatments().length > 0) return null;
    if (!this.hasGeneralConsent()) {
      return 'Este paciente no tiene firmado el consentimiento general del centro.';
    }
    return 'Este paciente no tiene tratamientos consentidos y aptos para este profesional.';
  });

  //máquinas compatibles con el tratamiento elegido (objeto completo, para conocer su sala fija)
  private readonly compatibleMachines = computed<Machine[]>(() => {
    const treatment = this.selectedTreatment();
    if (treatment === null) return [];
    const ids = new Set(treatment.machines.map((machine) => machine.id));
    return this.machines().filter((machine) => ids.has(machine.id));
  });

  protected readonly machineOptions = computed<SelectOption[]>(() => [
    { value: '', label: 'Sin máquina' },
    ...this.compatibleMachines().map((machine) => ({ value: String(machine.id), label: machine.name })),
  ]);

  protected readonly roomOptions = computed<SelectOption[]>(() =>
    this.rooms().map((room) => ({ value: String(room.id), label: room.name })),
  );

  private readonly selectedMachine = computed<Machine | null>(
    () =>
      this.compatibleMachines().find((machine) => String(machine.id) === this.formValue().machine_id) ??
      null,
  );

  //una máquina fija ata la cita a su sala
  protected readonly roomLocked = computed<boolean>(() => {
    const machine = this.selectedMachine();
    return machine !== null && !machine.is_mobile && machine.fixed_room_id !== null;
  });

  protected readonly assistantOptions = computed(() =>
    this.workers()
      .filter((worker) => String(worker.id) !== this.formValue().worker_id)
      .map((worker) => ({ id: worker.id, name: worker.name })),
  );

  //bloqueo progresivo de secciones
  protected readonly treatmentDisabled = computed(
    () => this.formValue().client_id === '' || this.formValue().worker_id === '',
  );
  protected readonly machineDisabled = computed(() => this.formValue().treatment_id === '');
  protected readonly roomDisabled = computed(
    () => this.formValue().treatment_id === '' || this.roomLocked(),
  );
  protected readonly timeDisabled = computed(
    () =>
      this.formValue().worker_id === '' ||
      this.formValue().date === '' ||
      this.formValue().treatment_id === '' ||
      this.formValue().room_id === '',
  );

  //inicios de cita disponibles para el profesional, con sala y máquina libres
  private readonly availableStartTimes = computed<string[]>(() => {
    const value = this.formValue();
    const workerId = value.worker_id === '' ? null : Number(value.worker_id);
    const date = value.date;
    const treatment = this.selectedTreatment();
    const roomId = value.room_id === '' ? null : Number(value.room_id);
    const machineId = value.machine_id === '' ? null : Number(value.machine_id);
    if (workerId === null || date === '' || treatment === null || roomId === null) return [];

    const weekday = this.isoWeekday(date);
    const duration = treatment.duration_minutes;

    const windows: [number, number][] = [];
    for (const schedule of this.schedules()) {
      if (
        schedule.worker_id !== workerId ||
        schedule.weekday !== weekday ||
        !this.scheduleActiveOn(schedule, date) ||
        !schedule.time_slot
      ) {
        continue;
      }
      const start = this.toMinutes(schedule.time_slot.start_time);
      const end = this.toMinutes(schedule.time_slot.end_time);
      if (schedule.time_slot.break_start && schedule.time_slot.break_end) {
        windows.push([start, this.toMinutes(schedule.time_slot.break_start)]);
        windows.push([this.toMinutes(schedule.time_slot.break_end), end]);
      } else {
        windows.push([start, end]);
      }
    }
    for (const extra of this.extras()) {
      if (extra.worker_id !== workerId || extra.date !== date) continue;
      windows.push([this.toMinutes(extra.start_time), this.toMinutes(extra.end_time)]);
    }

    const merged = this.mergeWindows(windows);
    if (merged.length === 0) return this.withCurrentTime([], value.time);

    const absent: [number, number][] = [];
    for (const absence of this.absences()) {
      if (absence.worker_id !== workerId || absence.date !== date) continue;
      if (absence.is_full_day) return this.withCurrentTime([], value.time);
      if (absence.start_time && absence.end_time) {
        absent.push([this.toMinutes(absence.start_time), this.toMinutes(absence.end_time)]);
      }
    }

    const editingId = this.appointment()?.id ?? null;
    const busy: [number, number][] = [];
    for (const item of this.dayAppointments()) {
      if (editingId !== null && item.id === editingId) continue;
      const start = this.minutesOfIso(item.starts_at);
      const end = this.minutesOfIso(item.ends_at);
      if (start === null || end === null) continue;
      const conflicts =
        item.worker?.id === workerId ||
        item.room?.id === roomId ||
        (machineId !== null && item.machine?.id === machineId);
      if (conflicts) busy.push([start, end]);
    }

    const times: string[] = [];
    for (const [windowStart, windowEnd] of merged) {
      let start = Math.ceil(windowStart / SLOT_STEP_MINUTES) * SLOT_STEP_MINUTES;
      for (; start + duration <= windowEnd; start += SLOT_STEP_MINUTES) {
        const end = start + duration;
        if (this.overlaps(start, end, absent) || this.overlaps(start, end, busy)) continue;
        times.push(this.minutesToHHMM(start));
      }
    }
    return this.withCurrentTime(times, value.time);
  });

  protected readonly timeOptions = computed<SelectOption[]>(() =>
    this.availableStartTimes().map((time) => ({ value: time, label: time })),
  );

  protected readonly timeHint = computed<string | null>(() => {
    if (this.timeDisabled() || this.availabilityLoading()) return null;
    if (this.availableStartTimes().length === 0) {
      return 'No hay huecos para esta combinación. Prueba otra fecha, profesional o sala.';
    }
    return null;
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

  constructor() {
    effect((onCleanup) => {
      const update = () => this.isMobile.set(window.innerWidth < 768);
      update();
      window.addEventListener('resize', update);
      onCleanup(() => window.removeEventListener('resize', update));
    });

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
        this.selectedClientLabel.set(current.client?.name ?? null);
        if (current.client) void this.loadConsents(current.client.id);
        if (date !== '') void this.loadDayBundle(date);
      } else {
        const confirmada =
          this.createStatusOptions().find((option) => this.optionIsConfirmada(option))?.value ??
          this.createStatusOptions()[0]?.value ??
          '';
        const date = prefill?.date ?? this.today();
        this.form.reset({
          client_id: '',
          treatment_id: '',
          worker_id: prefill ? String(prefill.worker_id) : '',
          room_id: '',
          machine_id: '',
          date,
          time: prefill?.time ?? '',
          status_id: confirmada,
          reserved_price: null,
          notes: '',
          assistant_ids: [],
        });
        this.selectedClientLabel.set(null);
        this.clientResults.set([]);
        this.clientConsents.set([]);
        this.hasGeneralConsent.set(false);
        this.lastConsentClientId = 0;
        if (date !== '') void this.loadDayBundle(date);
      }
    });
  }

  protected async onClientSearch(query: string): Promise<void> {
    this.clientLoading.set(true);
    try {
      const page = await this.clientService.list({ search: query, is_active: true, per_page: 20 });
      this.clientResults.set(
        page.data.map((client) => ({
          value: String(client.id),
          label: client.name,
          sublabel: client.phone ?? client.email ?? undefined,
        })),
      );
    } catch {
      this.clientResults.set([]);
    } finally {
      this.clientLoading.set(false);
    }
  }

  protected async onClientPicked(option: SearchSelectOption): Promise<void> {
    this.form.controls.client_id.setValue(option.value);
    this.selectedClientLabel.set(option.label);
    await this.loadConsents(Number(option.value));
    if (!this.isTreatmentEligible(this.form.controls.treatment_id.value)) {
      this.clearTreatmentCascade();
    }
  }

  protected onWorkerChange(value: string): void {
    this.form.controls.worker_id.setValue(value);
    const numeric = Number(value);
    this.form.controls.assistant_ids.setValue(
      this.form.controls.assistant_ids.value.filter((id) => id !== numeric),
    );
    if (!this.isTreatmentEligible(this.form.controls.treatment_id.value)) {
      this.clearTreatmentCascade();
    }
    this.form.controls.time.setValue('');
  }

  protected onTreatmentChange(value: string): void {
    this.form.controls.treatment_id.setValue(value);
    const treatment = this.treatments().find((item) => String(item.id) === value);
    //prefijar el precio reservado con la tarifa del tratamiento si esta vacio
    if (this.form.controls.reserved_price.value === null && treatment) {
      this.form.controls.reserved_price.setValue(Number(treatment.price));
    }
    //descartar la máquina elegida si ya no es compatible con el nuevo tratamiento
    const machineValue = this.form.controls.machine_id.value;
    const compatibleIds = new Set(treatment?.machines.map((machine) => machine.id) ?? []);
    if (machineValue !== '' && !compatibleIds.has(Number(machineValue))) {
      this.form.controls.machine_id.setValue('');
    }
    this.form.controls.time.setValue('');
  }

  protected onMachineChange(value: string): void {
    this.form.controls.machine_id.setValue(value);
    const machine = this.compatibleMachines().find((item) => String(item.id) === value);
    if (machine && !machine.is_mobile && machine.fixed_room_id !== null) {
      this.form.controls.room_id.setValue(String(machine.fixed_room_id));
    }
    this.form.controls.time.setValue('');
  }

  protected onRoomChange(value: string): void {
    this.form.controls.room_id.setValue(value);
    this.form.controls.time.setValue('');
  }

  protected onDateChange(date: string | null): void {
    if (date === null || date === '') return;
    this.form.controls.time.setValue('');
    void this.loadDayBundle(date);
  }

  private async loadConsents(clientId: number): Promise<void> {
    if (clientId === this.lastConsentClientId) return;
    this.lastConsentClientId = clientId;
    this.consentLoading.set(true);
    try {
      const active = await this.consentService.activeConsentsFor(clientId);
      this.clientConsents.set(active.treatments);
      this.hasGeneralConsent.set(active.client !== null);
    } catch {
      this.clientConsents.set([]);
      this.hasGeneralConsent.set(false);
    } finally {
      this.consentLoading.set(false);
    }
  }

  private async loadDayBundle(date: string): Promise<void> {
    if (date === this.lastBundleDate) return;
    this.lastBundleDate = date;
    this.availabilityLoading.set(true);
    try {
      const [schedules, absences, extras, appointments] = await Promise.all([
        this.scheduleService.listCenter(this.isoWeekday(date)),
        this.absenceService.listCenter(date, date),
        this.extraService.listCenter(date, date),
        this.appointmentService.listRange(date, this.nextDay(date)),
      ]);
      this.schedules.set(schedules);
      this.absences.set(absences);
      this.extras.set(extras);
      this.dayAppointments.set(appointments);
    } catch {
      this.schedules.set([]);
      this.absences.set([]);
      this.extras.set([]);
      this.dayAppointments.set([]);
    } finally {
      this.availabilityLoading.set(false);
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
      starts_at: toOffsetIso(start),
      ends_at: toOffsetIso(end),
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

  private isTreatmentEligible(treatmentId: string): boolean {
    if (treatmentId === '') return false;
    return this.eligibleTreatments().some((treatment) => String(treatment.id) === treatmentId);
  }

  private clearTreatmentCascade(): void {
    this.form.controls.treatment_id.setValue('');
    this.form.controls.machine_id.setValue('');
    this.form.controls.time.setValue('');
  }

  private withCurrentTime(times: string[], current: string): string[] {
    const unique = Array.from(new Set(times)).sort();
    if (this.isEdit() && current !== '' && !unique.includes(current)) {
      return [current, ...unique].sort();
    }
    return unique;
  }

  private mergeWindows(windows: [number, number][]): [number, number][] {
    const sorted = windows.filter(([start, end]) => end > start).sort((a, b) => a[0] - b[0]);
    const merged: [number, number][] = [];
    for (const [start, end] of sorted) {
      const last = merged[merged.length - 1];
      if (last && start <= last[1]) {
        last[1] = Math.max(last[1], end);
      } else {
        merged.push([start, end]);
      }
    }
    return merged;
  }

  private overlaps(start: number, end: number, intervals: [number, number][]): boolean {
    return intervals.some(([from, to]) => start < to && end > from);
  }

  private optionIsConfirmada(option: SelectOption): boolean {
    const status = this.lookup.sessionStatuses().find((item) => String(item.id) === option.value);
    return status ? this.normalize(status.name) === 'confirmada' : false;
  }

  private statusIdFor(code: string): number | null {
    const status = this.lookup.sessionStatuses().find((item) => this.normalize(item.name) === code);
    return status ? status.id : null;
  }

  private normalize(name: string): string {
    return name.toLowerCase().trim().replace(/\s+/g, '_');
  }

  private toMinutes(time: string): number {
    const [hours, minutes] = time.split(':');
    return Number(hours) * 60 + Number(minutes);
  }

  private minutesToHHMM(value: number): string {
    return `${pad(Math.floor(value / 60))}:${pad(value % 60)}`;
  }

  private minutesOfIso(iso: string | null): number | null {
    if (iso === null) return null;
    const parsed = new Date(iso);
    return Number.isNaN(parsed.getTime()) ? null : parsed.getHours() * 60 + parsed.getMinutes();
  }

  private scheduleActiveOn(schedule: WorkerSchedule, day: string): boolean {
    const startsOk = schedule.start_date === null || schedule.start_date <= day;
    const endsOk = schedule.end_date === null || schedule.end_date >= day;
    return startsOk && endsOk;
  }

  private isoWeekday(day: string): number {
    const parsed = new Date(`${day}T00:00:00`);
    const js = parsed.getDay();
    return js === 0 ? 7 : js;
  }

  private nextDay(day: string): string {
    const parsed = new Date(`${day}T00:00:00`);
    parsed.setDate(parsed.getDate() + 1);
    return formatLocalDate(parsed);
  }

  private splitIso(iso: string | null): { date: string; time: string } {
    if (iso === null) return { date: this.today(), time: '' };
    const parsed = new Date(iso);
    return {
      date: formatLocalDate(parsed),
      time: `${pad(parsed.getHours())}:${pad(parsed.getMinutes())}`,
    };
  }

  private today(): string {
    return formatLocalDate(new Date());
  }
}
