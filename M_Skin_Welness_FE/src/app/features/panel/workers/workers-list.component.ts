import { TitleCasePipe } from '@angular/common';
import { Component, computed, inject, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { HttpErrorResponse } from '@angular/common/http';
import { WorkerService, CreateWorkerData, UpdateWorkerData } from '../../../core/services/worker.service';
import { AuthService } from '../../../core/services/auth.service';
import { LookupService } from '../../../core/services/lookup.service';
import { NotificationService } from '../../../core/services/notification.service';
import { User } from '../../../core/models/user.model';
import { PaginatedMeta } from '../../../core/models/paginated.model';
import { apiError, loadResourceError } from '../../../core/utils/form.util';
import { AlertComponent } from '../../../shared/ui/alert/alert.component';
import { SegmentedControlComponent, SegmentedControlOption } from '../../../shared/ui/segmented-control/segmented-control.component';
import { SelectComponent, SelectOption } from '../../../shared/ui/select/select.component';
import { TableScrollHintComponent } from '../../../shared/ui/table-scroll-hint/table-scroll-hint.component';
import { WorkerModalComponent, WorkerFormValue } from './modals/worker-modal/worker-modal.component';
import { TableLoadingOverlayComponent } from '../../../shared/ui/table-loading-overlay/table-loading-overlay.component';
import { SearchInputComponent } from '../../../shared/ui/search-input/search-input.component';

type ActiveFilter = 'all' | 'active' | 'inactive';

const ACTIVE_FILTER_OPTIONS: SegmentedControlOption<ActiveFilter>[] = [
  { value: 'all', label: 'Todos' },
  { value: 'active', label: 'Activos' },
  { value: 'inactive', label: 'Inactivos' },
];

@Component({
  selector: 'app-workers-list',
  standalone: true,
  imports: [
    TitleCasePipe,
    RouterLink,
    AlertComponent,
    SegmentedControlComponent,
    SelectComponent,
    TableScrollHintComponent,
    WorkerModalComponent,
    TableLoadingOverlayComponent,
    SearchInputComponent,
  ],
  templateUrl: './workers-list.component.html',
})
export class WorkersListComponent {
  private readonly workers = inject(WorkerService);
  private readonly notifications = inject(NotificationService);
  protected readonly auth = inject(AuthService);
  private readonly lookups = inject(LookupService);

  protected readonly items = signal<User[]>([]);
  protected readonly meta = signal<PaginatedMeta | null>(null);
  protected readonly loading = signal(false);
  protected readonly errorMessage = signal<string | null>(null);

  protected readonly searchInput = signal('');
  protected readonly activeFilter = signal<ActiveFilter>('active');
  protected readonly roleFilter = signal('');
  protected readonly page = signal(1);

  protected readonly activeFilterOptions = ACTIVE_FILTER_OPTIONS;

  protected readonly roleOptions = computed<SelectOption[]>(() => [
    { value: '', label: 'Todos los roles' },
    ...this.lookups
      .roles()
      .filter((role) => role.name !== 'cliente' && role.name !== 'superadmin')
      .map((role) => ({ value: role.name, label: role.name })),
  ]);

  protected readonly modalOpen = signal(false);
  protected readonly editingWorker = signal<User | null>(null);
  protected readonly submitting = signal(false);

  protected readonly busyRowIds = signal<Set<number>>(new Set());

  constructor() {
    void this.load();
  }

  protected isRowBusy(userId: number): boolean {
    return this.busyRowIds().has(userId);
  }

  protected onSearch(value: string): void {
    this.searchInput.set(value);
    this.page.set(1);
    void this.load();
  }

  protected onActiveFilterChange(value: ActiveFilter): void {
    this.activeFilter.set(value);
    this.page.set(1);
    void this.load();
  }

  protected onRoleFilterChange(value: string): void {
    this.roleFilter.set(value);
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
    this.editingWorker.set(null);
    this.modalOpen.set(true);
  }

  protected openEditModal(worker: User): void {
    this.editingWorker.set(worker);
    this.modalOpen.set(true);
  }

  protected closeModal(): void {
    if (this.submitting()) return;
    this.modalOpen.set(false);
  }

  async submitWorker(value: WorkerFormValue): Promise<void> {
    const editing = this.editingWorker();
    this.submitting.set(true);
    try {
      if (editing !== null) {
        const data: UpdateWorkerData = {
          name: value.name,
          email: value.email === '' ? null : value.email,
          phone: value.phone === '' ? null : value.phone,
          birth_date: value.birth_date === '' ? null : value.birth_date,
        };
        const updated = await this.workers.update(editing.id, data);
        this.replaceItem(updated);
        this.modalOpen.set(false);
        this.notifications.toast.success('Trabajador actualizado.');
      } else {
        const data: CreateWorkerData = {
          name: value.name,
          email: value.email === '' ? null : value.email,
          phone: value.phone === '' ? null : value.phone,
          birth_date: value.birth_date === '' ? null : value.birth_date,
          role_ids: value.role_ids,
          password: value.password === '' ? null : value.password,
          is_active: value.is_active,
        };
        await this.workers.create(data);
        this.modalOpen.set(false);
        this.notifications.toast.success('Trabajador dado de alta.');
        this.page.set(1);
        await this.load();
      }
    } catch (error) {
      this.notifications.toast.error(apiError(error));
    } finally {
      this.submitting.set(false);
    }
  }

  async toggleActive(worker: User): Promise<void> {
    if (this.isRowBusy(worker.id)) return;

    const action = worker.is_active ? 'desactivar' : 'reactivar';
    const confirmed = await this.notifications.modal.confirm({
      variant: worker.is_active ? 'warning' : 'info',
      title: worker.is_active ? '¿Desactivar al trabajador?' : '¿Reactivar al trabajador?',
      message: worker.is_active
        ? `El trabajador "${worker.name}" no podrá acceder al panel ni aparecer en la agenda. Su historial se conserva.`
        : `Se restaurará el acceso del trabajador "${worker.name}" al centro.`,
      confirmText: worker.is_active ? 'Sí, desactivar' : 'Sí, reactivar',
      cancelText: 'Cancelar',
    });
    if (!confirmed) return;

    this.markBusy(worker.id, true);
    try {
      if (worker.is_active) {
        await this.workers.deactivate(worker.id);
        this.replaceItem({ ...worker, is_active: false });
      } else {
        const updated = await this.workers.activate(worker.id);
        this.replaceItem(updated);
      }
      this.notifications.toast.success(
        worker.is_active ? 'Trabajador desactivado.' : 'Trabajador reactivado.',
      );
    } catch (error) {
      const message = (error as HttpErrorResponse).error?.message ?? `No se ha podido ${action} al trabajador. Vuelve a intentarlo en unos segundos.`;
      this.notifications.toast.error(message);
    } finally {
      this.markBusy(worker.id, false);
    }
  }

  protected async load(): Promise<void> {
    this.loading.set(true);
    this.errorMessage.set(null);
    try {
      const result = await this.workers.list({
        search: this.searchInput(),
        role: this.roleFilter(),
        is_active: this.activeFilterValue(),
        page: this.page(),
      });
      this.items.set(result.data);
      this.meta.set(result.meta);
    } catch {
      const message = loadResourceError('los trabajadores');
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

  private replaceItem(updated: User): void {
    this.items.update((items) => items.map((item) => (item.id === updated.id ? updated : item)));
  }

  private markBusy(userId: number, busy: boolean): void {
    this.busyRowIds.update((current) => {
      const next = new Set(current);
      if (busy) {
        next.add(userId);
      } else {
        next.delete(userId);
      }
      return next;
    });
  }
}
