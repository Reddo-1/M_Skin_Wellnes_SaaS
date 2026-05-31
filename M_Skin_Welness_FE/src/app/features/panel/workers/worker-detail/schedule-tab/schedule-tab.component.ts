import { Component, computed, effect, inject, input, signal } from '@angular/core';
import { FullCalendarModule } from '@fullcalendar/angular';
import { CalendarOptions, EventInput } from '@fullcalendar/core';
import esLocale from '@fullcalendar/core/locales/es';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import interactionPlugin from '@fullcalendar/interaction';
import { AuthService } from '../../../../../core/services/auth.service';
import { NotificationService } from '../../../../../core/services/notification.service';
import { WorkerScheduleService } from '../../../../../core/services/worker-schedule.service';
import { WorkerAbsenceService } from '../../../../../core/services/worker-absence.service';
import { WorkerExtraAvailabilityService } from '../../../../../core/services/worker-extra-availability.service';
import { WorkerSchedule } from '../../../../../core/models/worker-schedule.model';
import { WorkerAbsence } from '../../../../../core/models/worker-absence.model';
import { WorkerExtraAvailability } from '../../../../../core/models/worker-extra-availability.model';
import { apiError, loadResourceError } from '../../../../../core/utils/form.util';
import { AlertComponent } from '../../../../../shared/ui/alert/alert.component';
import { ScheduleModalComponent, ScheduleFormValue } from './modals/schedule-modal/schedule-modal.component';
import { AbsenceModalComponent, AbsenceFormValue } from './modals/absence-modal/absence-modal.component';
import { ExtraModalComponent, ExtraFormValue } from './modals/extra-modal/extra-modal.component';

type ScheduleEventKind = 'schedule' | 'absence' | 'extra';

@Component({
  selector: 'app-worker-schedule-tab',
  standalone: true,
  imports: [
    FullCalendarModule,
    AlertComponent,
    ScheduleModalComponent,
    AbsenceModalComponent,
    ExtraModalComponent,
  ],
  templateUrl: './schedule-tab.component.html',
})
export class ScheduleTabComponent {
  readonly workerId = input.required<number>();

  protected readonly auth = inject(AuthService);
  private readonly notifications = inject(NotificationService);
  private readonly scheduleService = inject(WorkerScheduleService);
  private readonly absenceService = inject(WorkerAbsenceService);
  private readonly extraService = inject(WorkerExtraAvailabilityService);

  protected readonly schedules = signal<WorkerSchedule[]>([]);
  protected readonly absences = signal<WorkerAbsence[]>([]);
  protected readonly extras = signal<WorkerExtraAvailability[]>([]);
  protected readonly loading = signal(false);
  protected readonly errorMessage = signal<string | null>(null);
  protected readonly isMobile = signal(false);

  protected readonly scheduleModalOpen = signal(false);
  protected readonly absenceModalOpen = signal(false);
  protected readonly extraModalOpen = signal(false);
  protected readonly submitting = signal(false);
  protected readonly editingSchedule = signal<WorkerSchedule | null>(null);
  protected readonly editingAbsence = signal<WorkerAbsence | null>(null);
  protected readonly editingExtra = signal<WorkerExtraAvailability | null>(null);

  protected readonly calendarOptions = computed<CalendarOptions>(() => {
    const mobile = this.isMobile();
    return {
      plugins: [timeGridPlugin, listPlugin, interactionPlugin],
      initialView: mobile ? 'listWeek' : 'timeGridWeek',
      locale: esLocale,
      firstDay: 1,
      allDaySlot: true,
      slotMinTime: '07:00:00',
      slotMaxTime: '22:00:00',
      nowIndicator: true,
      height: 'auto',
      expandRows: true,
      stickyHeaderDates: true,
      headerToolbar: mobile
        ? { left: 'title', center: '', right: 'prev,next today' }
        : { left: 'prev,next today', center: 'title', right: 'timeGridWeek,timeGridDay,listWeek' },
      noEventsText: 'Sin franjas, ausencias ni extras esta semana',
      events: this.calendarEvents(),
      eventClick: (arg) =>
        this.onEventClick(
          arg.event.extendedProps['kind'] as ScheduleEventKind,
          arg.event.extendedProps['id'] as number,
        ),
    };
  });

  private readonly calendarEvents = computed<EventInput[]>(() => {
    const events: EventInput[] = [];

    for (const schedule of this.schedules()) {
      const slot = schedule.time_slot;
      const daysOfWeek = [schedule.weekday === 7 ? 0 : schedule.weekday];
      const startRecur = schedule.start_date ?? undefined;
      const endRecur = schedule.end_date ? this.dayAfter(schedule.end_date) : undefined;
      const block = {
        title: slot?.name ?? 'Franja',
        daysOfWeek,
        startRecur,
        endRecur,
        backgroundColor: '#dbeafe',
        borderColor: '#3b82f6',
        textColor: '#1e3a8a',
        extendedProps: { kind: 'schedule', id: schedule.id },
      };

      if (slot?.break_start && slot?.break_end) {
        //se parte la franja en dos bloques dejando el descanso como hueco + banda rayada
        //(se omite el bloque si el descanso queda pegado a un extremo, para no pintar duracion cero)
        if (slot.break_start !== slot.start_time) {
          events.push({ ...block, startTime: slot.start_time, endTime: slot.break_start });
        }
        if (slot.break_end !== slot.end_time) {
          events.push({ ...block, startTime: slot.break_end, endTime: slot.end_time });
        }
        events.push({
          daysOfWeek,
          startRecur,
          endRecur,
          startTime: slot.break_start,
          endTime: slot.break_end,
          display: 'background',
          classNames: ['fc-break-bg'],
        });
      } else {
        events.push({
          ...block,
          startTime: slot?.start_time ?? undefined,
          endTime: slot?.end_time ?? undefined,
        });
      }
    }

    for (const absence of this.absences()) {
      events.push({
        title: absence.absence_type?.name ?? 'Ausencia',
        start: absence.is_full_day ? (absence.date ?? undefined) : `${absence.date}T${absence.start_time}`,
        end: absence.is_full_day ? undefined : `${absence.date}T${absence.end_time}`,
        allDay: absence.is_full_day,
        backgroundColor: '#fee2e2',
        borderColor: '#ef4444',
        textColor: '#991b1b',
        extendedProps: { kind: 'absence', id: absence.id },
      });
    }

    for (const extra of this.extras()) {
      events.push({
        title: extra.reason ?? 'Disponibilidad extra',
        start: `${extra.date}T${extra.start_time}`,
        end: `${extra.date}T${extra.end_time}`,
        backgroundColor: '#dcfce7',
        borderColor: '#22c55e',
        textColor: '#166534',
        extendedProps: { kind: 'extra', id: extra.id },
      });
    }

    return events;
  });

  constructor() {
    effect((onCleanup) => {
      const update = () => this.isMobile.set(window.innerWidth < 768);
      update();
      window.addEventListener('resize', update);
      onCleanup(() => window.removeEventListener('resize', update));
    });

    effect(() => {
      const workerId = this.workerId();
      if (workerId > 0) {
        void this.load(workerId);
      }
    });
  }

  private async load(workerId: number): Promise<void> {
    this.loading.set(true);
    this.errorMessage.set(null);
    try {
      const [schedules, absences, extras] = await Promise.all([
        this.scheduleService.list(workerId),
        this.absenceService.list(workerId),
        this.extraService.list(workerId),
      ]);
      this.schedules.set(schedules.data);
      this.absences.set(absences.data);
      this.extras.set(extras.data);
    } catch {
      this.errorMessage.set(loadResourceError('el horario del trabajador'));
    } finally {
      this.loading.set(false);
    }
  }

  protected openScheduleModal(): void {
    this.editingSchedule.set(null);
    this.scheduleModalOpen.set(true);
  }

  protected openAbsenceModal(): void {
    this.editingAbsence.set(null);
    this.absenceModalOpen.set(true);
  }

  protected openExtraModal(): void {
    this.editingExtra.set(null);
    this.extraModalOpen.set(true);
  }

  protected closeScheduleModal(): void {
    if (this.submitting()) return;
    this.scheduleModalOpen.set(false);
  }

  protected closeAbsenceModal(): void {
    if (this.submitting()) return;
    this.absenceModalOpen.set(false);
  }

  protected closeExtraModal(): void {
    if (this.submitting()) return;
    this.extraModalOpen.set(false);
  }

  async submitSchedule(value: ScheduleFormValue): Promise<void> {
    const workerId = this.workerId();
    const editing = this.editingSchedule();
    this.submitting.set(true);
    try {
      if (editing) {
        await this.scheduleService.update(editing.id, { worker_id: workerId, ...value });
        this.notifications.toast.success('Franja actualizada.');
      } else {
        await this.scheduleService.create({ worker_id: workerId, ...value });
        this.notifications.toast.success('Franja asignada.');
      }
      this.scheduleModalOpen.set(false);
      await this.load(workerId);
    } catch (error) {
      this.notifications.toast.error(apiError(error));
    } finally {
      this.submitting.set(false);
    }
  }

  async submitAbsence(value: AbsenceFormValue): Promise<void> {
    const workerId = this.workerId();
    const editing = this.editingAbsence();
    this.submitting.set(true);
    try {
      if (editing) {
        await this.absenceService.update(editing.id, {
          date: value.date,
          is_full_day: value.is_full_day,
          start_time: value.start_time,
          end_time: value.end_time,
          reason: value.reason,
          absence_type_id: value.absence_type_id,
          notes: value.notes,
        });
        this.notifications.toast.success('Ausencia actualizada.');
      } else {
        await this.absenceService.create({
          worker_id: workerId,
          from: value.from,
          to: value.to,
          is_full_day: value.is_full_day,
          start_time: value.start_time,
          end_time: value.end_time,
          reason: value.reason,
          absence_type_id: value.absence_type_id,
          notes: value.notes,
        });
        this.notifications.toast.success('Ausencia registrada.');
      }
      this.absenceModalOpen.set(false);
      await this.load(workerId);
    } catch (error) {
      this.notifications.toast.error(apiError(error));
    } finally {
      this.submitting.set(false);
    }
  }

  async submitExtra(value: ExtraFormValue): Promise<void> {
    const workerId = this.workerId();
    const editing = this.editingExtra();
    this.submitting.set(true);
    try {
      if (editing) {
        await this.extraService.update(editing.id, { worker_id: workerId, ...value });
        this.notifications.toast.success('Disponibilidad actualizada.');
      } else {
        await this.extraService.create({ worker_id: workerId, ...value });
        this.notifications.toast.success('Disponibilidad añadida.');
      }
      this.extraModalOpen.set(false);
      await this.load(workerId);
    } catch (error) {
      this.notifications.toast.error(apiError(error));
    } finally {
      this.submitting.set(false);
    }
  }

  async deleteSchedule(): Promise<void> {
    const editing = this.editingSchedule();
    if (editing === null) return;
    const confirmed = await this.notifications.modal.confirm({
      variant: 'warning',
      title: '¿Eliminar la franja?',
      message: 'Se quitará esta franja recurrente del horario del trabajador.',
      confirmText: 'Sí, eliminar',
      cancelText: 'Cancelar',
    });
    if (!confirmed) return;
    this.submitting.set(true);
    try {
      await this.scheduleService.delete(editing.id);
      this.scheduleModalOpen.set(false);
      this.notifications.toast.success('Franja eliminada.');
      await this.load(this.workerId());
    } catch (error) {
      this.notifications.toast.error(apiError(error));
    } finally {
      this.submitting.set(false);
    }
  }

  async deleteAbsence(): Promise<void> {
    const editing = this.editingAbsence();
    if (editing === null) return;
    const confirmed = await this.notifications.modal.confirm({
      variant: 'warning',
      title: '¿Eliminar la ausencia?',
      message: 'Se eliminará la ausencia de este día.',
      confirmText: 'Sí, eliminar',
      cancelText: 'Cancelar',
    });
    if (!confirmed) return;
    this.submitting.set(true);
    try {
      await this.absenceService.delete(editing.id);
      this.absenceModalOpen.set(false);
      this.notifications.toast.success('Ausencia eliminada.');
      await this.load(this.workerId());
    } catch (error) {
      this.notifications.toast.error(apiError(error));
    } finally {
      this.submitting.set(false);
    }
  }

  async deleteExtra(): Promise<void> {
    const editing = this.editingExtra();
    if (editing === null) return;
    const confirmed = await this.notifications.modal.confirm({
      variant: 'warning',
      title: '¿Eliminar la disponibilidad extra?',
      message: 'Se eliminará este tramo de disponibilidad puntual.',
      confirmText: 'Sí, eliminar',
      cancelText: 'Cancelar',
    });
    if (!confirmed) return;
    this.submitting.set(true);
    try {
      await this.extraService.delete(editing.id);
      this.extraModalOpen.set(false);
      this.notifications.toast.success('Disponibilidad eliminada.');
      await this.load(this.workerId());
    } catch (error) {
      this.notifications.toast.error(apiError(error));
    } finally {
      this.submitting.set(false);
    }
  }

  private onEventClick(kind: ScheduleEventKind, id: number): void {
    if (kind === 'schedule') {
      if (!this.auth.hasPermission('worker_schedules.update')) return;
      const found = this.schedules().find((item) => item.id === id);
      if (!found) return;
      this.editingSchedule.set(found);
      this.scheduleModalOpen.set(true);
    } else if (kind === 'absence') {
      if (!this.auth.hasPermission('worker_absences.update')) return;
      const found = this.absences().find((item) => item.id === id);
      if (!found) return;
      this.editingAbsence.set(found);
      this.absenceModalOpen.set(true);
    } else {
      if (!this.auth.hasPermission('worker_extra_availabilities.update')) return;
      const found = this.extras().find((item) => item.id === id);
      if (!found) return;
      this.editingExtra.set(found);
      this.extraModalOpen.set(true);
    }
  }

  private dayAfter(date: string): string {
    const parsed = new Date(`${date}T00:00:00`);
    parsed.setDate(parsed.getDate() + 1);
    const month = `${parsed.getMonth() + 1}`.padStart(2, '0');
    const day = `${parsed.getDate()}`.padStart(2, '0');
    return `${parsed.getFullYear()}-${month}-${day}`;
  }
}
