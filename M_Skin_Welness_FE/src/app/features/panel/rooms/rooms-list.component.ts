import { Component, inject, signal } from '@angular/core';
import { RoomService, RoomData } from '../../../core/services/room.service';
import { AuthService } from '../../../core/services/auth.service';
import { NotificationService } from '../../../core/services/notification.service';
import { Room } from '../../../core/models/room.model';
import { PaginatedMeta } from '../../../core/models/paginated.model';
import { apiError, loadResourceError } from '../../../core/utils/form.util';
import { AlertComponent } from '../../../shared/ui/alert/alert.component';
import { SegmentedControlComponent, SegmentedControlOption } from '../../../shared/ui/segmented-control/segmented-control.component';
import { TableScrollHintComponent } from '../../../shared/ui/table-scroll-hint/table-scroll-hint.component';
import { RoomModalComponent, RoomFormValue } from './modals/room-modal/room-modal.component';
import { LoadingOverlayComponent } from '../../../shared/ui/table-loading-overlay/table-loading-overlay.component';

type ActiveFilter = 'all' | 'active' | 'inactive';

const ACTIVE_FILTER_OPTIONS: SegmentedControlOption<ActiveFilter>[] = [
  { value: 'all', label: 'Todas' },
  { value: 'active', label: 'Activas' },
  { value: 'inactive', label: 'Inactivas' },
];

@Component({
  selector: 'app-rooms-list',
  standalone: true,
  imports: [AlertComponent, SegmentedControlComponent, TableScrollHintComponent, RoomModalComponent, LoadingOverlayComponent],
  templateUrl: './rooms-list.component.html',
})
export class RoomsListComponent {
  private readonly rooms = inject(RoomService);
  protected readonly auth = inject(AuthService);
  private readonly notifications = inject(NotificationService);

  protected readonly items = signal<Room[]>([]);
  protected readonly meta = signal<PaginatedMeta | null>(null);
  protected readonly loading = signal(false);
  protected readonly errorMessage = signal<string | null>(null);

  protected readonly activeFilter = signal<ActiveFilter>('active');
  protected readonly page = signal(1);
  protected readonly activeFilterOptions = ACTIVE_FILTER_OPTIONS;

  protected readonly modalOpen = signal(false);
  protected readonly editingRoom = signal<Room | null>(null);
  protected readonly submitting = signal(false);

  protected readonly busyRowIds = signal<Set<number>>(new Set());

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
    this.editingRoom.set(null);
    this.modalOpen.set(true);
  }

  protected openEditModal(room: Room): void {
    this.editingRoom.set(room);
    this.modalOpen.set(true);
  }

  protected closeModal(): void {
    if (this.submitting()) return;
    this.modalOpen.set(false);
  }

  async submitRoom(value: RoomFormValue): Promise<void> {
    this.submitting.set(true);

    const payload: RoomData = {
      name: value.name,
      is_active: value.is_active,
    };

    const editing = this.editingRoom();

    try {
      if (editing !== null) {
        const updated = await this.rooms.update(editing.id, payload);
        this.replaceItem(updated);
        this.notifications.toast.success('Sala actualizada.');
      } else {
        await this.rooms.create(payload);
        this.notifications.toast.success('Sala creada.');
        this.page.set(1);
        await this.load();
      }
      this.modalOpen.set(false);
    } catch (error) {
      this.notifications.toast.error(apiError(error));
    } finally {
      this.submitting.set(false);
    }
  }

  async toggleActive(room: Room): Promise<void> {
    if (this.isRowBusy(room.id)) return;

    const confirmed = await this.notifications.modal.confirm({
      variant: room.is_active ? 'warning' : 'info',
      title: room.is_active ? '¿Desactivar esta sala?' : '¿Reactivar esta sala?',
      message: room.is_active
        ? `La sala "${room.name}" dejará de aparecer en los listados activos y no podrá asignarse a tratamientos ni sesiones. Su historial se conserva.`
        : `La sala "${room.name}" volverá a estar disponible en el centro.`,
      confirmText: room.is_active ? 'Sí, desactivar' : 'Sí, reactivar',
      cancelText: 'Cancelar',
    });
    if (!confirmed) return;

    this.markBusy(room.id, true);
    try {
      const updated = await this.rooms.setActive(room.id, !room.is_active);
      this.replaceItem(updated);
      this.notifications.toast.success(
        room.is_active ? 'Sala desactivada.' : 'Sala reactivada.',
      );
    } catch (error) {
      this.notifications.toast.error(apiError(error));
    } finally {
      this.markBusy(room.id, false);
    }
  }

  async deleteRoom(room: Room): Promise<void> {
    if (this.isRowBusy(room.id)) return;

    const confirmed = await this.notifications.modal.confirm({
      variant: 'warning',
      title: '¿Eliminar esta sala?',
      message: `La sala "${room.name}" se eliminará del centro. Si tiene citas asociadas no se podrá borrar.`,
      confirmText: 'Sí, eliminar',
      cancelText: 'Cancelar',
    });
    if (!confirmed) return;

    this.markBusy(room.id, true);
    try {
      await this.rooms.delete(room.id);
      this.items.update((items) => items.filter((item) => item.id !== room.id));
      this.notifications.toast.success('Sala eliminada.');
    } catch (error) {
      this.notifications.toast.error(apiError(error));
    } finally {
      this.markBusy(room.id, false);
    }
  }

  protected async load(): Promise<void> {
    this.loading.set(true);
    this.errorMessage.set(null);
    try {
      const result = await this.rooms.list({
        is_active: this.activeFilterValue(),
        page: this.page(),
      });
      this.items.set(result.data);
      this.meta.set(result.meta);
    } catch {
      const message = loadResourceError('las salas');
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

  private replaceItem(updated: Room): void {
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
