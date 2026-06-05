import { Component, inject, signal } from '@angular/core';
import { HttpErrorResponse } from '@angular/common/http';
import { MachineService, MachineData } from '../../../core/services/machine.service';
import { RoomService } from '../../../core/services/room.service';
import { AuthService } from '../../../core/services/auth.service';
import { NotificationService } from '../../../core/services/notification.service';
import { Machine } from '../../../core/models/machine.model';
import { LookupItem } from '../../../core/models/lookup.model';
import { PaginatedMeta } from '../../../core/models/paginated.model';
import { GENERIC_ERROR, loadResourceError } from '../../../core/utils/form.util';
import { AlertComponent } from '../../../shared/ui/alert/alert.component';
import { SegmentedControlComponent, SegmentedControlOption } from '../../../shared/ui/segmented-control/segmented-control.component';
import { TableScrollHintComponent } from '../../../shared/ui/table-scroll-hint/table-scroll-hint.component';
import { MachineModalComponent, MachineFormValue } from './modals/machine-modal/machine-modal.component';
import { TableLoadingOverlayComponent } from '../../../shared/ui/table-loading-overlay/table-loading-overlay.component';

type ActiveFilter = 'all' | 'active' | 'inactive';

const ACTIVE_FILTER_OPTIONS: SegmentedControlOption<ActiveFilter>[] = [
  { value: 'all', label: 'Todos' },
  { value: 'active', label: 'Activas' },
  { value: 'inactive', label: 'Inactivas' },
];

@Component({
  selector: 'app-machines-list',
  standalone: true,
  imports: [AlertComponent, SegmentedControlComponent, TableScrollHintComponent, MachineModalComponent, TableLoadingOverlayComponent],
  templateUrl: './machines-list.component.html',
})
export class MachinesListComponent {
  private readonly machines = inject(MachineService);
  private readonly rooms = inject(RoomService);
  protected readonly auth = inject(AuthService);
  private readonly notifications = inject(NotificationService);

  protected readonly items = signal<Machine[]>([]);
  protected readonly meta = signal<PaginatedMeta | null>(null);
  protected readonly loading = signal(false);
  protected readonly errorMessage = signal<string | null>(null);

  protected readonly roomOptions = signal<LookupItem[]>([]);

  protected readonly activeFilter = signal<ActiveFilter>('active');
  protected readonly page = signal(1);
  protected readonly activeFilterOptions = ACTIVE_FILTER_OPTIONS;

  protected readonly modalOpen = signal(false);
  protected readonly editingMachine = signal<Machine | null>(null);
  protected readonly submitting = signal(false);

  protected readonly busyRowIds = signal<Set<number>>(new Set());

  constructor() {
    void this.load();
    void this.loadRooms();
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
    this.editingMachine.set(null);
    this.modalOpen.set(true);
  }

  protected openEditModal(machine: Machine): void {
    this.editingMachine.set(machine);
    this.modalOpen.set(true);
  }

  protected closeModal(): void {
    if (this.submitting()) return;
    this.modalOpen.set(false);
  }

  async submitMachine(value: MachineFormValue): Promise<void> {
    this.submitting.set(true);

    const payload: MachineData = {
      name: value.name,
      is_mobile: value.is_mobile,
      fixed_room_id: value.fixed_room_id,
      is_active: value.is_active,
    };

    const editing = this.editingMachine();

    try {
      if (editing !== null) {
        const updated = await this.machines.update(editing.id, payload);
        this.replaceItem(updated);
        this.notifications.toast.success('Máquina actualizada.');
      } else {
        await this.machines.create(payload);
        this.notifications.toast.success('Máquina creada.');
        this.page.set(1);
        await this.load();
      }
      this.modalOpen.set(false);
    } catch (error) {
      const message = (error as HttpErrorResponse).error?.message ?? GENERIC_ERROR;
      this.notifications.toast.error(message);
    } finally {
      this.submitting.set(false);
    }
  }

  async toggleActive(machine: Machine): Promise<void> {
    if (this.isRowBusy(machine.id)) return;

    const action = machine.is_active ? 'desactivar' : 'reactivar';
    const confirmed = await this.notifications.modal.confirm({
      variant: machine.is_active ? 'warning' : 'info',
      title: machine.is_active ? '¿Desactivar esta máquina?' : '¿Reactivar esta máquina?',
      message: machine.is_active
        ? `La máquina "${machine.name}" dejará de aparecer en los listados activos y no podrá asignarse a tratamientos ni sesiones. Su historial se conserva.`
        : `La máquina "${machine.name}" volverá a estar disponible en el centro.`,
      confirmText: machine.is_active ? 'Sí, desactivar' : 'Sí, reactivar',
      cancelText: 'Cancelar',
    });
    if (!confirmed) return;

    this.markBusy(machine.id, true);
    try {
      const updated = await this.machines.setActive(machine.id, !machine.is_active);
      this.replaceItem(updated);
      this.notifications.toast.success(
        machine.is_active ? 'Máquina desactivada.' : 'Máquina reactivada.',
      );
    } catch (error) {
      const message = (error as HttpErrorResponse).error?.message ?? `No se ha podido ${action} la máquina. Vuelve a intentarlo en unos segundos.`;
      this.notifications.toast.error(message);
    } finally {
      this.markBusy(machine.id, false);
    }
  }

  async deleteMachine(machine: Machine): Promise<void> {
    if (this.isRowBusy(machine.id)) return;

    const confirmed = await this.notifications.modal.confirm({
      variant: 'warning',
      title: '¿Eliminar esta máquina?',
      message: `La máquina "${machine.name}" se eliminará del centro. Si tiene sesiones asociadas no se podrá borrar.`,
      confirmText: 'Sí, eliminar',
      cancelText: 'Cancelar',
    });
    if (!confirmed) return;

    this.markBusy(machine.id, true);
    try {
      await this.machines.delete(machine.id);
      this.items.update((items) => items.filter((item) => item.id !== machine.id));
      this.notifications.toast.success('Máquina eliminada.');
    } catch (error) {
      const message = (error as HttpErrorResponse).error?.message ?? 'No se ha podido eliminar la máquina.';
      this.notifications.toast.error(message);
    } finally {
      this.markBusy(machine.id, false);
    }
  }

  protected async load(): Promise<void> {
    this.loading.set(true);
    this.errorMessage.set(null);
    try {
      const result = await this.machines.list({
        is_active: this.activeFilterValue(),
        page: this.page(),
      });
      this.items.set(result.data);
      this.meta.set(result.meta);
    } catch {
      const message = loadResourceError('las máquinas');
      this.errorMessage.set(message);
    } finally {
      this.loading.set(false);
    }
  }

  private async loadRooms(): Promise<void> {
    try {
      const result = await this.rooms.list({ is_active: true });
      this.roomOptions.set(result.data.map((room) => ({ id: room.id, name: room.name })));
    } catch {
      this.roomOptions.set([]);
    }
  }

  private activeFilterValue(): boolean | undefined {
    const value = this.activeFilter();
    if (value === 'active') return true;
    if (value === 'inactive') return false;
    return undefined;
  }

  private replaceItem(updated: Machine): void {
    this.items.update((items) => items.map((item) => (item.id === updated.id ? updated : item)));
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
