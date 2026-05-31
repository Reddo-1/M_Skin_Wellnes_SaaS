import { Component, computed, effect, inject, signal } from '@angular/core';
import { FullCalendarModule } from '@fullcalendar/angular';
import {
  CalendarOptions,
  DateSelectArg,
  DatesSetArg,
  EventClickArg,
  EventDropArg,
  EventInput,
} from '@fullcalendar/core';
import esLocale from '@fullcalendar/core/locales/es';
import interactionPlugin, { EventResizeDoneArg } from '@fullcalendar/interaction';
import listPlugin from '@fullcalendar/list';
import resourceTimeGridPlugin from '@fullcalendar/resource-timegrid';
import { AppointmentSummary } from '../../../core/models/appointment.model';
import { Machine } from '../../../core/models/machine.model';
import { Room } from '../../../core/models/room.model';
import { Treatment } from '../../../core/models/treatment.model';
import { User } from '../../../core/models/user.model';
import { WorkerAbsence } from '../../../core/models/worker-absence.model';
import { WorkerExtraAvailability } from '../../../core/models/worker-extra-availability.model';
import { WorkerSchedule } from '../../../core/models/worker-schedule.model';
import { AppointmentService } from '../../../core/services/appointment.service';
import { AuthService } from '../../../core/services/auth.service';
import { ClientService } from '../../../core/services/client.service';
import { MachineService } from '../../../core/services/machine.service';
import { NotificationService } from '../../../core/services/notification.service';
import { RoomService } from '../../../core/services/room.service';
import { TreatmentService } from '../../../core/services/treatment.service';
import { WorkerService } from '../../../core/services/worker.service';
import { WorkerAbsenceService } from '../../../core/services/worker-absence.service';
import { WorkerExtraAvailabilityService } from '../../../core/services/worker-extra-availability.service';
import { WorkerScheduleService } from '../../../core/services/worker-schedule.service';
import { apiError, loadResourceError } from '../../../core/utils/form.util';
import { formatLocalDate, pad, toOffsetIso } from '../../../core/utils/datetime.util';
import { AlertComponent } from '../../../shared/ui/alert/alert.component';
import {
  AppointmentFormValue,
  AppointmentModalComponent,
  AppointmentPrefill,
} from './modals/appointment-modal/appointment-modal.component';

interface EventStyle {
  bg: string;
  border: string;
  text: string;
}

//colores de cita por estado, alineados a la paleta de marca y semanticos
const STATUS_STYLE: Record<string, EventStyle> = {
  pendiente: { bg: '#fffaeb', border: '#f79009', text: '#b54708' },
  confirmada: { bg: '#fef4ee', border: '#e6621f', text: '#c84c14' },
  en_curso: { bg: '#fde6d3', border: '#c84c14', text: '#6b2b10' },
  realizada: { bg: '#ecfdf3', border: '#12b76a', text: '#039855' },
  cancelada: { bg: '#f2f4f7', border: '#98a2b3', text: '#667085' },
  no_presentada: { bg: '#fef3f2', border: '#f04438', text: '#d92d20' },
};
const STATUS_FALLBACK: EventStyle = { bg: '#f2f4f7', border: '#98a2b3', text: '#667085' };

const DAY_START = '07:00:00';
const DAY_END = '22:00:00';

//estados en los que la cita se puede arrastrar/redimensionar en el cuadrante
const EDITABLE_STATUSES = ['pendiente', 'confirmada'];

@Component({
  selector: 'app-cuadrante',
  standalone: true,
  imports: [FullCalendarModule, AlertComponent, AppointmentModalComponent],
  templateUrl: './cuadrante.component.html',
})
export class CuadranteComponent {
  protected readonly auth = inject(AuthService);
  private readonly notifications = inject(NotificationService);
  private readonly appointmentService = inject(AppointmentService);
  private readonly workerService = inject(WorkerService);
  private readonly clientService = inject(ClientService);
  private readonly treatmentService = inject(TreatmentService);
  private readonly roomService = inject(RoomService);
  private readonly machineService = inject(MachineService);
  private readonly scheduleService = inject(WorkerScheduleService);
  private readonly absenceService = inject(WorkerAbsenceService);
  private readonly extraService = inject(WorkerExtraAvailabilityService);

  protected readonly workers = signal<User[]>([]);
  protected readonly clients = signal<User[]>([]);
  protected readonly treatments = signal<Treatment[]>([]);
  protected readonly rooms = signal<Room[]>([]);
  protected readonly machines = signal<Machine[]>([]);

  protected readonly appointments = signal<AppointmentSummary[]>([]);
  private readonly schedules = signal<WorkerSchedule[]>([]);
  private readonly absences = signal<WorkerAbsence[]>([]);
  private readonly extras = signal<WorkerExtraAvailability[]>([]);

  protected readonly loading = signal(false);
  protected readonly errorMessage = signal<string | null>(null);
  protected readonly isMobile = signal(false);

  protected readonly modalOpen = signal(false);
  protected readonly submitting = signal(false);
  protected readonly editingAppointment = signal<AppointmentSummary | null>(null);
  protected readonly prefill = signal<AppointmentPrefill | null>(null);

  private readonly currentDay = signal<string>('');
  private readonly currentFrom = signal<string>('');
  private readonly currentTo = signal<string>('');

  protected readonly canCreate = computed(() => this.auth.hasPermission('appointments.create'));
  protected readonly canManage = computed(() => this.auth.hasPermission('appointments.update'));

  //admin/recepcion/rrhh ven todas las columnas; un profesional solo la suya
  private readonly seesAllColumns = computed(() => {
    const roles = this.auth.effectiveRoles();
    return roles.some((role) => ['administrador', 'recepcionista', 'rrhh'].includes(role));
  });

  protected readonly resources = computed(() => {
    const all = this.workers().map((worker) => ({ id: String(worker.id), title: worker.name }));
    if (this.seesAllColumns()) return all;
    const selfId = String(this.auth.user()?.id ?? '');
    return all.filter((resource) => resource.id === selfId);
  });

  private readonly visibleResourceIds = computed(
    () => new Set(this.resources().map((resource) => resource.id)),
  );

  private readonly calendarEvents = computed<EventInput[]>(() => {
    const visible = this.visibleResourceIds();
    const day = this.currentDay();
    const events: EventInput[] = [];

    for (const appointment of this.appointments()) {
      const resourceId = appointment.worker ? String(appointment.worker.id) : '';
      if (!visible.has(resourceId)) continue;
      const status = this.normalize(appointment.status?.name);
      const style = STATUS_STYLE[status] ?? STATUS_FALLBACK;
      events.push({
        id: `appt-${appointment.id}`,
        resourceId,
        title: `${appointment.treatment?.name ?? 'Cita'} · ${appointment.client?.name ?? ''}`,
        start: appointment.starts_at ?? undefined,
        end: appointment.ends_at ?? undefined,
        editable: EDITABLE_STATUSES.includes(status),
        backgroundColor: style.bg,
        borderColor: style.border,
        textColor: style.text,
        extendedProps: { kind: 'appointment', id: appointment.id },
      });
    }

    if (day !== '') {
      const weekday = this.isoWeekday(day);
      for (const schedule of this.schedules()) {
        const resourceId = String(schedule.worker_id);
        if (!visible.has(resourceId)) continue;
        if (schedule.weekday !== weekday || !this.scheduleActiveOn(schedule, day)) continue;
        const slot = schedule.time_slot;
        if (!slot) continue;
        events.push({
          resourceId,
          start: `${day}T${slot.start_time}`,
          end: `${day}T${slot.end_time}`,
          display: 'background',
          backgroundColor: '#f2f4f7',
          extendedProps: { kind: 'window' },
        });
        if (slot.break_start && slot.break_end) {
          events.push({
            resourceId,
            start: `${day}T${slot.break_start}`,
            end: `${day}T${slot.break_end}`,
            display: 'background',
            classNames: ['fc-break-bg'],
            extendedProps: { kind: 'break' },
          });
        }
      }

      for (const absence of this.absences()) {
        const resourceId = String(absence.worker_id);
        if (!visible.has(resourceId) || absence.date !== day) continue;
        events.push({
          resourceId,
          start: absence.is_full_day ? `${day}T${DAY_START}` : `${day}T${absence.start_time}`,
          end: absence.is_full_day ? `${day}T${DAY_END}` : `${day}T${absence.end_time}`,
          display: 'background',
          backgroundColor: 'rgba(240, 68, 56, 0.12)',
          extendedProps: { kind: 'absence' },
        });
      }

      for (const extra of this.extras()) {
        const resourceId = String(extra.worker_id);
        if (!visible.has(resourceId) || extra.date !== day) continue;
        events.push({
          resourceId,
          start: `${day}T${extra.start_time}`,
          end: `${day}T${extra.end_time}`,
          display: 'background',
          backgroundColor: 'rgba(18, 183, 106, 0.14)',
          extendedProps: { kind: 'extra' },
        });
      }
    }

    return events;
  });

  protected readonly calendarOptions = computed<CalendarOptions>(() => {
    const mobile = this.isMobile();
    const manage = this.canManage();
    return {
      plugins: [resourceTimeGridPlugin, interactionPlugin, listPlugin],
      schedulerLicenseKey: 'GPL-My-Project-Is-Open-Source',
      initialView: mobile ? 'listDay' : 'resourceTimeGridDay',
      locale: esLocale,
      firstDay: 1,
      nowIndicator: true,
      allDaySlot: false,
      slotMinTime: DAY_START,
      slotMaxTime: DAY_END,
      height: 'auto',
      expandRows: true,
      stickyHeaderDates: true,
      headerToolbar: mobile
        ? { left: 'title', center: '', right: 'prev,next today' }
        : { left: 'prev,next today', center: 'title', right: '' },
      noEventsText: 'No hay citas este día',
      datesSet: (arg) => this.onDatesSet(arg),
      resources: this.resources(),
      events: this.calendarEvents(),
      selectable: manage,
      selectMirror: true,
      select: (arg) => this.onSelect(arg),
      editable: manage,
      eventResourceEditable: manage,
      eventClick: (arg) => this.onEventClick(arg),
      eventDrop: (arg) => this.onEventDrop(arg),
      eventResize: (arg) => this.onEventResize(arg),
    };
  });

  constructor() {
    effect((onCleanup) => {
      const update = () => this.isMobile.set(window.innerWidth < 768);
      update();
      window.addEventListener('resize', update);
      onCleanup(() => window.removeEventListener('resize', update));
    });

    void this.loadCatalogs();
  }

  private async loadCatalogs(): Promise<void> {
    try {
      const [clients, treatments, rooms, machines] = await Promise.all([
        this.clientService.list({ is_active: true, per_page: 200 }),
        this.treatmentService.list({ is_active: true, per_page: 200 }),
        this.roomService.list({ is_active: true, per_page: 200 }),
        this.machineService.list({ is_active: true, per_page: 200 }),
      ]);
      this.clients.set(clients.data);
      this.treatments.set(treatments.data);
      this.rooms.set(rooms.data);
      this.machines.set(machines.data);
      await this.loadWorkers();
    } catch {
      this.errorMessage.set(loadResourceError('los datos del cuadrante'));
    }
  }

  //solo admin/recepcion/rrhh pueden listar el personal; un profesional usa su propia ficha como unica columna
  private async loadWorkers(): Promise<void> {
    if (this.seesAllColumns()) {
      const workers = await this.workerService.list({ is_active: true, per_page: 200 });
      this.workers.set(workers.data);
    } else {
      const self = this.auth.user();
      this.workers.set(self ? [self] : []);
    }
  }

  private onDatesSet(arg: DatesSetArg): void {
    const day = formatLocalDate(arg.start);
    this.currentDay.set(day);
    this.currentFrom.set(arg.startStr);
    this.currentTo.set(arg.endStr);
    void this.loadDay();
  }

  private async loadDay(): Promise<void> {
    const day = this.currentDay();
    if (day === '') return;
    this.loading.set(true);
    this.errorMessage.set(null);
    try {
      const workerId = this.seesAllColumns() ? undefined : this.auth.user()?.id;
      const appointments = await this.appointmentService.listRange(
        this.currentFrom(),
        this.currentTo(),
        workerId,
      );
      this.appointments.set(appointments);
    } catch {
      this.errorMessage.set(loadResourceError('el cuadrante'));
      this.loading.set(false);
      return;
    }
    await this.loadAvailability(day);
    this.loading.set(false);
  }

  //overlay de disponibilidad: solo para quien puede leer horarios (admin/rrhh); el resto ve el cuadrante sin fondo
  private async loadAvailability(day: string): Promise<void> {
    if (!this.auth.hasPermission('worker_schedules.view')) {
      this.schedules.set([]);
      this.absences.set([]);
      this.extras.set([]);
      return;
    }
    try {
      const [schedules, absences, extras] = await Promise.all([
        this.scheduleService.listCenter(this.isoWeekday(day)),
        this.absenceService.listCenter(day, day),
        this.extraService.listCenter(day, day),
      ]);
      this.schedules.set(schedules);
      this.absences.set(absences);
      this.extras.set(extras);
    } catch {
      this.schedules.set([]);
      this.absences.set([]);
      this.extras.set([]);
    }
  }

  protected openCreate(): void {
    this.editingAppointment.set(null);
    this.prefill.set(null);
    this.modalOpen.set(true);
  }

  private onSelect(arg: DateSelectArg): void {
    if (!this.canManage()) return;
    const resourceId = arg.resource?.id;
    if (resourceId === undefined) return;
    this.editingAppointment.set(null);
    this.prefill.set({
      worker_id: Number(resourceId),
      date: formatLocalDate(arg.start),
      time: `${pad(arg.start.getHours())}:${pad(arg.start.getMinutes())}`,
    });
    this.modalOpen.set(true);
  }

  private onEventClick(arg: EventClickArg): void {
    if (arg.event.extendedProps['kind'] !== 'appointment') return;
    const id = arg.event.extendedProps['id'] as number;
    const found = this.appointments().find((appointment) => appointment.id === id);
    if (!found) return;
    this.prefill.set(null);
    this.editingAppointment.set(found);
    this.modalOpen.set(true);
  }

  protected closeModal(): void {
    if (this.submitting()) return;
    this.modalOpen.set(false);
  }

  protected async submit(value: AppointmentFormValue): Promise<void> {
    const editing = this.editingAppointment();
    this.submitting.set(true);
    try {
      if (editing) {
        await this.appointmentService.update(editing.id, {
          treatment_id: value.treatment_id,
          room_id: value.room_id,
          worker_id: value.worker_id,
          machine_id: value.machine_id,
          starts_at: value.starts_at,
          ends_at: value.ends_at,
          reserved_price: value.reserved_price,
          notes: value.notes,
          assistant_ids: value.assistant_ids,
        });
        this.notifications.toast.success('Cita actualizada.');
      } else {
        await this.appointmentService.create({ ...value, booking_source: 'panel' });
        this.notifications.toast.success('Cita creada.');
      }
      this.modalOpen.set(false);
      await this.loadDay();
    } catch (error) {
      this.notifications.toast.error(apiError(error));
    } finally {
      this.submitting.set(false);
    }
  }

  protected async changeStatus(statusId: number): Promise<void> {
    const editing = this.editingAppointment();
    if (editing === null) return;
    this.submitting.set(true);
    try {
      await this.appointmentService.changeStatus(editing.id, statusId);
      this.modalOpen.set(false);
      this.notifications.toast.success('Estado de la cita actualizado.');
      await this.loadDay();
    } catch (error) {
      this.notifications.toast.error(apiError(error));
    } finally {
      this.submitting.set(false);
    }
  }

  protected async removeAppointment(): Promise<void> {
    const editing = this.editingAppointment();
    if (editing === null) return;
    const confirmed = await this.notifications.modal.confirm({
      variant: 'warning',
      title: '¿Eliminar la cita?',
      message: 'Se borrará la cita del cuadrante. Esta acción no se puede deshacer.',
      confirmText: 'Sí, eliminar',
      cancelText: 'Cancelar',
    });
    if (!confirmed) return;
    this.submitting.set(true);
    try {
      await this.appointmentService.delete(editing.id);
      this.modalOpen.set(false);
      this.notifications.toast.success('Cita eliminada.');
      await this.loadDay();
    } catch (error) {
      this.notifications.toast.error(apiError(error));
    } finally {
      this.submitting.set(false);
    }
  }

  private async onEventDrop(arg: EventDropArg): Promise<void> {
    if (arg.event.start === null) {
      arg.revert();
      return;
    }
    const id = arg.event.extendedProps['id'] as number;
    const resourceId = arg.event.getResources()[0]?.id;
    try {
      await this.appointmentService.update(id, {
        worker_id: resourceId !== undefined ? Number(resourceId) : undefined,
        starts_at: toOffsetIso(arg.event.start),
        ends_at: arg.event.end ? toOffsetIso(arg.event.end) : undefined,
      });
      this.notifications.toast.success('Cita reprogramada.');
      await this.loadDay();
    } catch (error) {
      arg.revert();
      this.notifications.toast.error(apiError(error));
    }
  }

  private async onEventResize(arg: EventResizeDoneArg): Promise<void> {
    if (arg.event.start === null || arg.event.end === null) {
      arg.revert();
      return;
    }
    const id = arg.event.extendedProps['id'] as number;
    try {
      //se envia tambien starts_at (aunque no cambie) para que el guard del back interprete inicio y fin en la misma zona
      await this.appointmentService.update(id, {
        starts_at: toOffsetIso(arg.event.start),
        ends_at: toOffsetIso(arg.event.end),
      });
      this.notifications.toast.success('Duración de la cita actualizada.');
      await this.loadDay();
    } catch (error) {
      arg.revert();
      this.notifications.toast.error(apiError(error));
    }
  }

  private normalize(name: string | undefined): string {
    return (name ?? '').toLowerCase().trim().replace(/\s+/g, '_');
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
}
