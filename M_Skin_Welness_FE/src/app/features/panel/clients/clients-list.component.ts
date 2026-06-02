import { DatePipe } from '@angular/common';
import { Component, computed, inject, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { ClientService, CreateClientData, UpdateClientData } from '../../../core/services/client.service';
import { AuthService } from '../../../core/services/auth.service';
import { LookupService } from '../../../core/services/lookup.service';
import { NotificationService } from '../../../core/services/notification.service';
import { User } from '../../../core/models/user.model';
import { PaginatedMeta } from '../../../core/models/paginated.model';
import { HttpErrorResponse } from '@angular/common/http';
import { GENERIC_ERROR, loadResourceError } from '../../../core/utils/form.util';
import { AlertComponent } from '../../../shared/ui/alert/alert.component';
import { SegmentedControlComponent, SegmentedControlOption } from '../../../shared/ui/segmented-control/segmented-control.component';
import { TableScrollHintComponent } from '../../../shared/ui/table-scroll-hint/table-scroll-hint.component';
import { ClientModalComponent, ClientFormValue } from './modals/client-modal/client-modal.component';
import { ActivateOnlineModalComponent, ActivateOnlineFormValue } from './modals/activate-online-modal/activate-online-modal.component';
import { LoadingOverlayComponent } from "../../../shared/ui/table-loading-overlay/table-loading-overlay.component";
import { SearchInputComponent } from '../../../shared/ui/search-input/search-input.component';

type ActiveFilter = 'all' | 'active' | 'inactive';

const ACTIVE_FILTER_OPTIONS: SegmentedControlOption<ActiveFilter>[] = [
  { value: 'all', label: 'Todos' },
  { value: 'active', label: 'Activos' },
  { value: 'inactive', label: 'Inactivos' },
];

@Component({
  selector: 'app-clients-list',
  standalone: true,
  imports: [
    DatePipe,
    RouterLink,
    AlertComponent,
    ClientModalComponent,
    ActivateOnlineModalComponent,
    SegmentedControlComponent,
    TableScrollHintComponent,
    LoadingOverlayComponent,
    SearchInputComponent
],
  templateUrl: './clients-list.component.html',
})
export class ClientsListComponent {
  private readonly clients = inject(ClientService);
  private readonly notifications = inject(NotificationService);
  protected readonly auth = inject(AuthService);
  private readonly lookups = inject(LookupService);

  protected readonly items = signal<User[]>([]);
  protected readonly meta = signal<PaginatedMeta | null>(null);
  protected readonly loading = signal(false);
  protected readonly errorMessage = signal<string | null>(null);

  protected readonly searchInput = signal('');
  protected readonly activeFilter = signal<ActiveFilter>('active');
  protected readonly page = signal(1);

  protected readonly activeFilterOptions = ACTIVE_FILTER_OPTIONS;

  private readonly clienteRoleId = computed(
    () => this.lookups.roles().find((role) => role.name === 'cliente')?.id ?? null,
  );

  protected readonly modalOpen = signal(false);
  protected readonly editingClient = signal<User | null>(null);
  protected readonly submitting = signal(false);

  protected readonly activateOnlineTarget = signal<User | null>(null);
  protected readonly submittingActivateOnline = signal(false);

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

  protected goToPage(page: number): void {
    const meta = this.meta();
    if (meta === null) return;
    if (page < 1 || page > meta.last_page) return;
    this.page.set(page);
    void this.load();
  }

  protected openCreateModal(): void {
    if (this.clienteRoleId() === null) {
      this.notifications.toast.error('No se han podido cargar los roles. Vuelve a intentarlo en unos segundos.');
      return;
    }
    this.editingClient.set(null);
    this.modalOpen.set(true);
  }

  protected openEditModal(client: User): void {
    this.editingClient.set(client);
    this.modalOpen.set(true);
  }

  protected closeModal(): void {
    if (this.submitting()) return;
    this.modalOpen.set(false);
  }

  async submitClient(value: ClientFormValue): Promise<void> {
    const editing = this.editingClient();
    const data: UpdateClientData = {
      name: value.name,
      email: value.email === '' ? null : value.email,
      phone: value.phone === '' ? null : value.phone,
      birth_date: value.birth_date === '' ? null : value.birth_date,
    };

    this.submitting.set(true);
    try {
      if (editing !== null) {
        const updated = await this.clients.update(editing.id, data);
        this.replaceItem(updated);
        this.modalOpen.set(false);
        this.notifications.toast.success('Cliente actualizado.');
      } else {
        const roleId = this.clienteRoleId();
        if (roleId === null) return;
        const payload: CreateClientData = { ...data, role_ids: [roleId] };
        await this.clients.create(payload);
        this.modalOpen.set(false);
        this.notifications.toast.success('Cliente dado de alta.');
        this.page.set(1);
        await this.load();
      }
    } catch (error) {
      const message = (error as HttpErrorResponse).error?.message ?? GENERIC_ERROR;
      this.notifications.toast.error(message);
    } finally {
      this.submitting.set(false);
    }
  }

  protected openActivateOnlineModal(target: User): void {
    this.activateOnlineTarget.set(target);
  }

  protected closeActivateOnlineModal(): void {
    if (this.submittingActivateOnline()) return;
    this.activateOnlineTarget.set(null);
  }

  async submitActivateOnline(value: ActivateOnlineFormValue): Promise<void> {
    const target = this.activateOnlineTarget();
    if (target === null) return;

    this.submittingActivateOnline.set(true);

    try {
      const updated = await this.clients.activateOnlineAccess(target.id, value.email);
      this.replaceItem(updated);
      this.activateOnlineTarget.set(null);
      this.notifications.toast.success('Correo de activación enviado al cliente.');
    } catch (error) {
      const message = (error as HttpErrorResponse).error?.message ?? GENERIC_ERROR;
      this.notifications.toast.error(message);
    } finally {
      this.submittingActivateOnline.set(false);
    }
  }

  async toggleActive(user: User): Promise<void> {
    if (this.isRowBusy(user.id)) return;

    const action = user.is_active ? 'desactivar' : 'reactivar';
    const confirmed = await this.notifications.modal.confirm({
      variant: user.is_active ? 'warning' : 'info',
      title: user.is_active ? '¿Desactivar al cliente?' : '¿Reactivar al cliente?',
      message: user.is_active
        ? `El cliente "${user.name}" no podrá acceder ni aparecer en los listados activos. Su historial se conserva.`
        : `Se restaurará el acceso del cliente "${user.name}" al centro.`,
      confirmText: user.is_active ? 'Sí, desactivar' : 'Sí, reactivar',
      cancelText: 'Cancelar',
    });
    if (!confirmed) return;

    this.markBusy(user.id, true);
    try {
      if (user.is_active) {
        await this.clients.deactivate(user.id);
        this.replaceItem({ ...user, is_active: false });
      } else {
        const updated = await this.clients.activate(user.id);
        this.replaceItem(updated);
      }
      this.notifications.toast.success(
        user.is_active ? 'Cliente desactivado.' : 'Cliente reactivado.',
      );
    } catch (error) {
      const message = (error as HttpErrorResponse).error?.message ?? `No se ha podido ${action} al cliente. Vuelve a intentarlo en unos segundos.`;
      this.notifications.toast.error(message);
    } finally {
      this.markBusy(user.id, false);
    }
  }

  protected async load(): Promise<void> {
    this.loading.set(true);
    this.errorMessage.set(null);
    try {
      const result = await this.clients.list({
        search: this.searchInput(),
        is_active: this.activeFilterValue(),
        page: this.page(),
      });
      this.items.set(result.data);
      this.meta.set(result.meta);
    } catch {
      const message = loadResourceError('los clientes');
      this.errorMessage.set(message);
      this.notifications.toast.error(message);
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
