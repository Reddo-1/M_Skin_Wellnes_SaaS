import { Component, computed, effect, inject, signal } from '@angular/core';
import { HttpErrorResponse } from '@angular/common/http';
import { TimeSlotService, TimeSlotData } from '../../../core/services/time-slot.service';
import { AuthService } from '../../../core/services/auth.service';
import { NotificationService } from '../../../core/services/notification.service';
import { TimeSlot } from '../../../core/models/time-slot.model';
import { PaginatedMeta } from '../../../core/models/paginated.model';
import { GENERIC_ERROR, loadResourceError } from '../../../core/utils/form.util';
import { AlertComponent } from '../../../shared/ui/alert/alert.component';
import { SegmentedControlComponent, SegmentedControlOption } from '../../../shared/ui/segmented-control/segmented-control.component';
import { TableScrollHintComponent } from '../../../shared/ui/table-scroll-hint/table-scroll-hint.component';
import { TimeSlotModalComponent, TimeSlotFormValue } from './modals/time-slot-modal/time-slot-modal.component';
import { TableLoadingOverlayComponent } from '../../../shared/ui/table-loading-overlay/table-loading-overlay.component';

type ActiveFilter = 'all' | 'active' | 'inactive';

const ACTIVE_FILTER_OPTIONS: SegmentedControlOption<ActiveFilter>[] = [
  { value: 'all', label: 'Todas' },
  { value: 'active', label: 'Activas' },
  { value: 'inactive', label: 'Inactivas' },
];

@Component({
  selector: 'app-time-slots-list',
  standalone: true,
  imports: [
    AlertComponent,
    SegmentedControlComponent,
    TableScrollHintComponent,
    TimeSlotModalComponent,
    TableLoadingOverlayComponent,
  ],
  templateUrl: './time-slots-list.component.html',
})
export class TimeSlotsListComponent {
  private readonly timeSlots = inject(TimeSlotService);
  private readonly notifications = inject(NotificationService);
  protected readonly auth = inject(AuthService);

  protected readonly items = signal<TimeSlot[]>([]);
  protected readonly meta = signal<PaginatedMeta | null>(null);
  protected readonly loading = signal(false);
  protected readonly errorMessage = signal<string | null>(null);

  protected readonly activeFilter = signal<ActiveFilter>('active');
  protected readonly page = signal(1);
  protected readonly activeFilterOptions = ACTIVE_FILTER_OPTIONS;

  protected readonly modalOpen = signal(false);
  protected readonly editingTimeSlot = signal<TimeSlot | null>(null);
  protected readonly submitting = signal(false);
  protected readonly busyRowIds = signal<Set<number>>(new Set());

  protected readonly canDelete = computed(() => this.auth.effectiveRoles().includes('administrador'));

  constructor() {
    void this.load();
  }

  protected isRowBusy(id: number): boolean {
    return this.busyRowIds().has(id);
  }

  protected onActiveFilterChange(value: ActiveFilter): void {
    this.activeFilter.set(value);
    this.page.set(1);
    void this.load();
  }

  protected goToPage(page: number): void {
    const meta = this.meta();
    if (meta === null) return;
    if (page < 1 || page > meta.last_page) return;
    this.page.set(page);
    void this.load();
  }

  protected openCreateModal(): void {
    this.editingTimeSlot.set(null);
    this.modalOpen.set(true);
  }

  protected openEditModal(timeSlot: TimeSlot): void {
    this.editingTimeSlot.set(timeSlot);
    this.modalOpen.set(true);
  }

  protected closeModal(): void {
    if (this.submitting()) return;
    this.modalOpen.set(false);
  }

  async submitTimeSlot(value: TimeSlotFormValue): Promise<void> {
    const payload: TimeSlotData = {
      name: value.name.trim() === '' ? null : value.name,
      start_time: value.start_time,
      end_time: value.end_time,
      break_start: value.break_start,
      break_end: value.break_end,
      is_active: value.is_active,
    };
    const editing = this.editingTimeSlot();
    this.submitting.set(true);
    try {
      if (editing !== null) {
        await this.timeSlots.update(editing.id, payload);
        this.notifications.toast.success('Franja actualizada.');
      } else {
        await this.timeSlots.create(payload);
        this.notifications.toast.success('Franja creada.');
        this.page.set(1);
      }
      this.modalOpen.set(false);
      await this.load();
    } catch (error) {
      const message = (error as HttpErrorResponse).error?.message ?? GENERIC_ERROR;
      this.notifications.toast.error(message);
    } finally {
      this.submitting.set(false);
    }
  }

  async toggleActive(timeSlot: TimeSlot): Promise<void> {
    if (this.isRowBusy(timeSlot.id)) return;

    const confirmed = await this.notifications.modal.confirm({
      variant: timeSlot.is_active ? 'warning' : 'info',
      title: timeSlot.is_active ? '¿Desactivar esta franja?' : '¿Reactivar esta franja?',
      message: timeSlot.is_active
        ? `La franja "${timeSlot.name ?? 'sin nombre'}" dejará de poder asignarse a trabajadores. Su historial se conserva.`
        : `La franja "${timeSlot.name ?? 'sin nombre'}" volverá a estar disponible para asignar.`,
      confirmText: timeSlot.is_active ? 'Sí, desactivar' : 'Sí, reactivar',
      cancelText: 'Cancelar',
    });
    if (!confirmed) return;

    this.markBusy(timeSlot.id, true);
    try {
      await this.timeSlots.setActive(timeSlot.id, !timeSlot.is_active);
      this.notifications.toast.success(
        timeSlot.is_active ? 'Franja desactivada.' : 'Franja reactivada.',
      );
      await this.load();
    } catch (error) {
      const message = (error as HttpErrorResponse).error?.message ?? 'No se ha podido cambiar el estado de la franja.';
      this.notifications.toast.error(message);
    } finally {
      this.markBusy(timeSlot.id, false);
    }
  }

  async deleteTimeSlot(timeSlot: TimeSlot): Promise<void> {
    if (this.isRowBusy(timeSlot.id)) return;

    const confirmed = await this.notifications.modal.confirm({
      variant: 'warning',
      title: '¿Eliminar esta franja?',
      message: `La franja "${timeSlot.name ?? 'sin nombre'}" se eliminará. Si está asignada a algún trabajador no se podrá borrar.`,
      confirmText: 'Sí, eliminar',
      cancelText: 'Cancelar',
    });
    if (!confirmed) return;

    this.markBusy(timeSlot.id, true);
    try {
      await this.timeSlots.delete(timeSlot.id);
      this.items.update((items) => items.filter((item) => item.id !== timeSlot.id));
      this.notifications.toast.success('Franja eliminada.');
    } catch (error) {
      const message = (error as HttpErrorResponse).error?.message ?? 'No se ha podido eliminar la franja.';
      this.notifications.toast.error(message);
    } finally {
      this.markBusy(timeSlot.id, false);
    }
  }

  protected async load(): Promise<void> {
    this.loading.set(true);
    this.errorMessage.set(null);
    try {
      const result = await this.timeSlots.list({
        is_active: this.activeFilterValue(),
        page: this.page(),
      });
      this.items.set(result.data);
      this.meta.set(result.meta);
    } catch {
      const message = loadResourceError('las franjas horarias');
      this.errorMessage.set(message);
    } finally {
      this.loading.set(false);
    }
  }

  private activeFilterValue(): boolean | undefined {
    const value = this.activeFilter();
    if (value === 'active') return true;
    if (value === 'inactive') return false;
    return undefined;
  }

  private markBusy(id: number, busy: boolean): void {
    this.busyRowIds.update((current) => {
      const next = new Set(current);
      if (busy) {
        next.add(id);
      } else {
        next.delete(id);
      }
      return next;
    });
  }
}
