import { Component, computed, effect, inject, input, output, signal } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { AppointmentSummary } from '../../../../../core/models/appointment.model';
import { Machine } from '../../../../../core/models/machine.model';
import { Product } from '../../../../../core/models/product.model';
import { Room } from '../../../../../core/models/room.model';
import { Treatment } from '../../../../../core/models/treatment.model';
import { User } from '../../../../../core/models/user.model';
import { WorkerSchedule } from '../../../../../core/models/worker-schedule.model';
import { WorkerAbsence } from '../../../../../core/models/worker-absence.model';
import { WorkerExtraAvailability } from '../../../../../core/models/worker-extra-availability.model';
import { AppointmentProductLine, AppointmentService } from '../../../../../core/services/appointment.service';
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
  notes: string | null;
  assistant_ids: number[];
}

export interface AppointmentPrefill {
  date: string;
}

type AppointmentField = 'client_id' | 'treatment_id' | 'worker_id' | 'room_id' | 'date' | 'time' | 'notes';

interface StatusAction {
  status_id: number;
  label: string;
  tone: 'success' | 'danger' | 'neutral';
}

//granularidad de los inicios de cita ofrecidos
const SLOT_STEP_MINUTES = 15;

@Component({
  selector: 'app-appointment-modal',
  standalone: true,
  imports: [
    ReactiveFormsModule,
    RouterLink,
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
  readonly products = input<Product[]>([]);
  readonly canManage = input<boolean>(false);
  readonly canChangeStatus = input<boolean>(false);
  readonly canViewClient = input<boolean>(false);

  readonly close = output<void>();
  readonly formSubmit = output<AppointmentFormValue>();
  readonly statusChange = output<number>();
  readonly finishSession = output<AppointmentProductLine[]>();
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

  //cierre de sesión con productos consumidos (en dosis)
  protected readonly finalizing = signal(false);
  protected readonly productLines = signal<{ id: number; name: string; quantity: number }[]>([]);

  protected readonly form = this.fb.nonNullable.group({
    client_id: ['', Validators.required],
    treatment_id: ['', Validators.required],
    worker_id: ['', Validators.required],
    room_id: ['', Validators.required],
    machine_id: [''],
    date: ['', Validators.required],
    time: ['', Validators.required],
    notes: ['', Validators.maxLength(5000)],
    assistant_ids: this.fb.nonNullable.control<number[]>([]),
  });

  //selector de productos consumidos en el paso de cierre de sesión
  protected readonly pickForm = this.fb.nonNullable.group({
    product_id: [''],
    quantity: this.fb.control<number | null>(null),
  });

  //tick reactivo del form; el valor se lee crudo (no opcional) dentro de un computed
  private readonly formChanges = toSignal(this.form.valueChanges, { initialValue: null });
  private readonly formValue = computed(() => {
    this.formChanges();
    return this.form.getRawValue();
  });

  protected readonly selectedTreatment = computed<Treatment | null>(
    () => this.treatments().find((item) => String(item.id) === this.formValue().treatment_id) ?? null,
  );

  //bloque reservado = duracion + margen del tratamiento
  protected readonly selectedDuration = computed<number | null>(() => {
    const treatment = this.selectedTreatment();
    return treatment ? treatment.duration_minutes + treatment.margin_minutes : null;
  });

  //tratamientos elegibles del paciente: consentidos y aptos (el profesional se elige despues, segun el tratamiento)
  private readonly eligibleTreatments = computed<Treatment[]>(() => {
    if (this.formValue().client_id === '') return [];
    const consentByTreatment = new Map(
      this.clientConsents().map((consent) => [consent.treatment_id, consent]),
    );
    return this.treatments().filter((treatment) => {
      const consent = consentByTreatment.get(treatment.id);
      return consent?.treatment_consent === true && consent?.is_suitable === true;
    });
  });

  protected readonly treatmentOptions = computed<SelectOption[]>(() => {
    const options = this.eligibleTreatments().map((treatment) => ({
      value: String(treatment.id),
      label: `${treatment.name} · ${treatment.duration_minutes} min`,
    }));
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
    return 'Este paciente no tiene tratamientos consentidos y aptos.';
  });

  //profesionales que pueden realizar el tratamiento elegido (rol autorizado)
  private readonly authorizedWorkers = computed<User[]>(() => {
    const treatment = this.selectedTreatment();
    if (treatment === null) return [];
    const roleNames = new Set(treatment.authorized_roles.map((role) => role.name));
    return this.workers().filter((worker) => worker.roles.some((role) => roleNames.has(role)));
  });

  protected readonly workerOptions = computed<SelectOption[]>(() => {
    const options = this.authorizedWorkers().map((worker) => ({
      value: String(worker.id),
      label: worker.name,
    }));
    const current = this.appointment()?.worker;
    if (current && !options.some((option) => option.value === String(current.id))) {
      options.unshift({ value: String(current.id), label: current.name });
    }
    return options;
  });

  protected readonly workerHint = computed<string | null>(() => {
    if (this.workerDisabled()) return null;
    return this.authorizedWorkers().length === 0
      ? 'Ningún profesional del centro está autorizado para este tratamiento.'
      : null;
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
    () => this.compatibleMachines().find((machine) => String(machine.id) === this.formValue().machine_id) ?? null,
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

  //bloqueo progresivo de secciones: paciente -> tratamiento -> profesional -> maquina/sala -> hora
  protected readonly treatmentDisabled = computed(() => this.formValue().client_id === '');
  protected readonly workerDisabled = computed(() => this.formValue().treatment_id === '');
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
    const duration = treatment.duration_minutes + treatment.margin_minutes;

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
    if (merged.length === 0) return this.withCurrentTime([]);

    const absent: [number, number][] = [];
    for (const absence of this.absences()) {
      if (absence.worker_id !== workerId || absence.date !== date) continue;
      if (absence.is_full_day) return this.withCurrentTime([]);
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
    return this.withCurrentTime(times);
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

  protected readonly isConfirmed = computed(
    () => this.normalize(this.appointment()?.status?.name ?? '') === 'confirmada',
  );
  protected readonly isInProgress = computed(
    () => this.normalize(this.appointment()?.status?.name ?? '') === 'en_curso',
  );

  //productos activos aún no añadidos a la sesión
  protected readonly availableProductOptions = computed<SelectOption[]>(() => {
    const added = new Set(this.productLines().map((line) => line.id));
    return this.products()
      .filter((product) => !added.has(product.id))
      .map((product) => ({ value: String(product.id), label: product.name }));
  });

  //acciones de estado segun el estado actual (Iniciar/Finalizar se gestionan aparte, con productos)
  protected readonly statusActions = computed<StatusAction[]>(() => {
    const current = this.appointment();
    if (current?.status == null) return [];
    const targets: { code: string; label: string; tone: StatusAction['tone'] }[] = [];
    if (this.normalize(current.status.name) === 'confirmada') {
      targets.push({ code: 'no_presentada', label: 'No presentada', tone: 'neutral' });
      targets.push({ code: 'cancelada', label: 'Cancelar', tone: 'danger' });
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
    effect(() => {
      if (!this.isOpen()) return;
      this.finalizing.set(false);
      this.productLines.set([]);
      //invalida las cachés de instancia para que cada apertura recargue datos frescos
      this.lastBundleDate = '';
      this.lastConsentClientId = 0;
      this.schedules.set([]);
      this.absences.set([]);
      this.extras.set([]);
      this.dayAppointments.set([]);
      this.clientConsents.set([]);
      this.hasGeneralConsent.set(false);
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
          notes: current.notes ?? '',
          assistant_ids: current.assistants?.map((assistant) => assistant.id) ?? [],
        });
        this.selectedClientLabel.set(current.client?.name ?? null);
        if (current.client) void this.loadConsents(current.client.id);
        if (date !== '') void this.loadDayBundle(date);
      } else {
        const date = prefill?.date ?? this.today();
        this.form.reset({
          client_id: '',
          treatment_id: '',
          worker_id: '',
          room_id: '',
          machine_id: '',
          date,
          time: '',
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
    //si el tratamiento ya elegido deja de ser elegible para el nuevo paciente, se limpia toda la cadena
    if (!this.isTreatmentEligible(this.form.controls.treatment_id.value)) {
      this.clearFromTreatment();
    }
  }

  protected onTreatmentChange(value: string): void {
    this.form.controls.treatment_id.setValue(value);
    //descartar el profesional si ya no está autorizado para el nuevo tratamiento
    if (!this.isWorkerAuthorized(this.form.controls.worker_id.value, value)) {
      this.form.controls.worker_id.setValue('');
    }
    //descartar la máquina si ya no es compatible con el nuevo tratamiento
    const treatment = this.treatments().find((item) => String(item.id) === value);
    const compatibleIds = new Set(treatment?.machines.map((machine) => machine.id) ?? []);
    if (this.form.controls.machine_id.value !== '' && !compatibleIds.has(Number(this.form.controls.machine_id.value))) {
      this.form.controls.machine_id.setValue('');
    }
    this.form.controls.time.setValue('');
  }

  protected onWorkerChange(value: string): void {
    this.form.controls.worker_id.setValue(value);
    const numeric = Number(value);
    this.form.controls.assistant_ids.setValue(
      this.form.controls.assistant_ids.value.filter((id) => id !== numeric),
    );
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
    const total = treatment ? treatment.duration_minutes + treatment.margin_minutes : 0;
    const start = new Date(`${raw.date}T${raw.time}:00`);
    const end = new Date(start.getTime() + total * 60000);

    this.formSubmit.emit({
      client_id: Number(raw.client_id),
      treatment_id: Number(raw.treatment_id),
      worker_id: Number(raw.worker_id),
      room_id: Number(raw.room_id),
      machine_id: raw.machine_id === '' ? null : Number(raw.machine_id),
      starts_at: toOffsetIso(start),
      ends_at: toOffsetIso(end),
      notes: raw.notes.trim() === '' ? null : raw.notes,
      assistant_ids: raw.assistant_ids,
    });
  }

  protected clientDetailLink(): string | null {
    const id = this.appointment()?.client?.id;
    return id !== undefined ? `/panel/clientes/${id}` : null;
  }

  //inicia la sesión (confirmada -> en curso)
  protected startSession(): void {
    const statusId = this.statusIdFor('en_curso');
    if (statusId !== null) this.statusChange.emit(statusId);
  }

  protected openFinalize(): void {
    this.productLines.set([]);
    this.pickForm.reset({ product_id: '', quantity: null });
    this.finalizing.set(true);
  }

  protected cancelFinalize(): void {
    this.finalizing.set(false);
  }

  protected addProductLine(): void {
    const productId = this.pickForm.controls.product_id.value;
    const quantity = this.pickForm.controls.quantity.value;
    if (productId === '' || quantity === null || quantity <= 0) return;
    const product = this.products().find((item) => String(item.id) === productId);
    if (!product) return;
    this.productLines.update((lines) => [...lines, { id: product.id, name: product.name, quantity }]);
    this.pickForm.reset({ product_id: '', quantity: null });
  }

  protected removeProductLine(id: number): void {
    this.productLines.update((lines) => lines.filter((line) => line.id !== id));
  }

  //cierra la sesión (en curso -> realizada) adjuntando los productos consumidos
  protected confirmFinalize(): void {
    if (this.submitting()) return;
    this.finishSession.emit(
      this.productLines().map((line) => ({ product_id: line.id, quantity: line.quantity })),
    );
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

  private isWorkerAuthorized(workerId: string, treatmentId: string): boolean {
    if (workerId === '' || treatmentId === '') return false;
    const treatment = this.treatments().find((item) => String(item.id) === treatmentId);
    const worker = this.workers().find((item) => String(item.id) === workerId);
    if (!treatment || !worker) return false;
    const roleNames = new Set(treatment.authorized_roles.map((role) => role.name));
    return worker.roles.some((role) => roleNames.has(role));
  }

  private clearFromTreatment(): void {
    this.form.controls.treatment_id.setValue('');
    this.form.controls.worker_id.setValue('');
    this.form.controls.machine_id.setValue('');
    this.form.controls.time.setValue('');
  }

  private withCurrentTime(times: string[]): string[] {
    const unique = Array.from(new Set(times)).sort();
    const current = this.formValue().time;
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
