import { CurrencyPipe, TitleCasePipe } from '@angular/common';
import { Component, inject, signal } from '@angular/core';
import { HttpErrorResponse } from '@angular/common/http';
import { TreatmentService, TreatmentData } from '../../../core/services/treatment.service';
import { MachineService } from '../../../core/services/machine.service';
import { AuthService } from '../../../core/services/auth.service';
import { NotificationService } from '../../../core/services/notification.service';
import { Treatment } from '../../../core/models/treatment.model';
import { LookupItem } from '../../../core/models/lookup.model';
import { PaginatedMeta } from '../../../core/models/paginated.model';
import { GENERIC_ERROR, loadResourceError } from '../../../core/utils/form.util';
import { AlertComponent } from '../../../shared/ui/alert/alert.component';
import { SegmentedControlComponent, SegmentedControlOption } from '../../../shared/ui/segmented-control/segmented-control.component';
import { TableScrollHintComponent } from '../../../shared/ui/table-scroll-hint/table-scroll-hint.component';
import { TreatmentModalComponent, TreatmentFormValue } from './modals/treatment-modal/treatment-modal.component';
import { LoadingOverlayComponent } from "../../../shared/ui/table-loading-overlay/table-loading-overlay.component";

type ActiveFilter = 'all' | 'active' | 'inactive';

const ACTIVE_FILTER_OPTIONS: SegmentedControlOption<ActiveFilter>[] = [
  { value: 'all', label: 'Todos' },
  { value: 'active', label: 'Activos' },
  { value: 'inactive', label: 'Inactivos' },
];

@Component({
  selector: 'app-treatments-list',
  standalone: true,
  imports: [CurrencyPipe, TitleCasePipe, AlertComponent, SegmentedControlComponent, TableScrollHintComponent, TreatmentModalComponent, LoadingOverlayComponent],
  templateUrl: './treatments-list.component.html',
})
export class TreatmentsListComponent {
  private readonly treatments = inject(TreatmentService);
  private readonly machines = inject(MachineService);
  protected readonly auth = inject(AuthService);
  private readonly notifications = inject(NotificationService);

  protected readonly items = signal<Treatment[]>([]);
  protected readonly meta = signal<PaginatedMeta | null>(null);
  protected readonly loading = signal(false);
  protected readonly errorMessage = signal<string | null>(null);

  protected readonly machineOptions = signal<LookupItem[]>([]);

  protected readonly activeFilter = signal<ActiveFilter>('active');
  protected readonly page = signal(1);
  protected readonly activeFilterOptions = ACTIVE_FILTER_OPTIONS;

  protected readonly modalOpen = signal(false);
  protected readonly editingTreatment = signal<Treatment | null>(null);
  protected readonly submitting = signal(false);

  protected readonly busyRowIds = signal<Set<number>>(new Set());

  constructor() {
    void this.load();
    void this.loadMachines();
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
    this.editingTreatment.set(null);
    this.modalOpen.set(true);
  }

  protected openEditModal(treatment: Treatment): void {
    this.editingTreatment.set(treatment);
    this.modalOpen.set(true);
  }

  protected closeModal(): void {
    if (this.submitting()) return;
    this.modalOpen.set(false);
  }

  async submitTreatment(value: TreatmentFormValue): Promise<void> {
    this.submitting.set(true);

    const payload: TreatmentData = {
      name: value.name,
      duration_minutes: value.duration_minutes,
      margin_minutes: value.margin_minutes,
      price: value.price,
      is_active: value.is_active,
      machine_ids: value.machine_ids,
      role_ids: value.role_ids,
    };

    const editing = this.editingTreatment();

    try {
      if (editing !== null) {
        const updated = await this.treatments.update(editing.id, payload);
        this.replaceItem(updated);
        this.notifications.toast.success('Tratamiento actualizado.');
      } else {
        await this.treatments.create(payload);
        this.notifications.toast.success('Tratamiento creado.');
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

  async toggleActive(treatment: Treatment): Promise<void> {
    if (this.isRowBusy(treatment.id)) return;

    const action = treatment.is_active ? 'desactivar' : 'reactivar';
    const confirmed = await this.notifications.modal.confirm({
      variant: treatment.is_active ? 'warning' : 'info',
      title: treatment.is_active ? '¿Desactivar este tratamiento?' : '¿Reactivar este tratamiento?',
      message: treatment.is_active
        ? `El tratamiento "${treatment.name}" dejará de aparecer en los listados activos y no podrá reservarse en nuevas citas. Su historial se conserva.`
        : `El tratamiento "${treatment.name}" volverá a estar disponible en el catálogo del centro.`,
      confirmText: treatment.is_active ? 'Sí, desactivar' : 'Sí, reactivar',
      cancelText: 'Cancelar',
    });
    if (!confirmed) return;

    this.markBusy(treatment.id, true);
    try {
      const updated = await this.treatments.setActive(treatment.id, !treatment.is_active);
      this.replaceItem(updated);
      this.notifications.toast.success(
        treatment.is_active ? 'Tratamiento desactivado.' : 'Tratamiento reactivado.',
      );
    } catch (error) {
      const message = (error as HttpErrorResponse).error?.message ?? `No se ha podido ${action} el tratamiento. Vuelve a intentarlo en unos segundos.`;
      this.notifications.toast.error(message);
    } finally {
      this.markBusy(treatment.id, false);
    }
  }

  async deleteTreatment(treatment: Treatment): Promise<void> {
    if (this.isRowBusy(treatment.id)) return;

    const confirmed = await this.notifications.modal.confirm({
      variant: 'warning',
      title: '¿Eliminar este tratamiento?',
      message: `El tratamiento "${treatment.name}" se eliminará del catálogo. Si tiene citas asociadas no se podrá borrar.`,
      confirmText: 'Sí, eliminar',
      cancelText: 'Cancelar',
    });
    if (!confirmed) return;

    this.markBusy(treatment.id, true);
    try {
      await this.treatments.delete(treatment.id);
      this.items.update((items) => items.filter((item) => item.id !== treatment.id));
      this.notifications.toast.success('Tratamiento eliminado.');
    } catch (error) {
      const message = (error as HttpErrorResponse).error?.message ?? 'No se ha podido eliminar el tratamiento.';
      this.notifications.toast.error(message);
    } finally {
      this.markBusy(treatment.id, false);
    }
  }

  protected async load(): Promise<void> {
    this.loading.set(true);
    this.errorMessage.set(null);
    try {
      const result = await this.treatments.list({
        is_active: this.activeFilterValue(),
        page: this.page(),
      });
      this.items.set(result.data);
      this.meta.set(result.meta);
    } catch {
      const message = loadResourceError('los tratamientos');
      this.errorMessage.set(message);
      this.notifications.toast.error(message);
    } finally {
      this.loading.set(false);
    }
  }

  private async loadMachines(): Promise<void> {
    try {
      const result = await this.machines.list({ is_active: true });
      this.machineOptions.set(result.data.map((machine) => ({ id: machine.id, name: machine.name })));
    } catch {
      this.machineOptions.set([]);
    }
  }

  private activeFilterValue(): boolean | undefined {
    const value = this.activeFilter();
    if (value === 'active') return true;
    if (value === 'inactive') return false;
    return undefined;
  }

  private replaceItem(updated: Treatment): void {
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
