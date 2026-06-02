import { Component, computed, effect, inject, input, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { HttpErrorResponse } from '@angular/common/http';
import { WorkerService } from '../../../../core/services/worker.service';
import { AuthService } from '../../../../core/services/auth.service';
import { NotificationService } from '../../../../core/services/notification.service';
import { User } from '../../../../core/models/user.model';
import { GENERIC_ERROR, loadResourceError } from '../../../../core/utils/form.util';
import { AlertComponent } from '../../../../shared/ui/alert/alert.component';
import { WorkerDataTabComponent } from './data-tab/data-tab.component';
import { ScheduleTabComponent } from './schedule-tab/schedule-tab.component';

type WorkerTab = 'datos' | 'horario';

@Component({
  selector: 'app-worker-detail',
  standalone: true,
  imports: [RouterLink, AlertComponent, WorkerDataTabComponent, ScheduleTabComponent],
  templateUrl: './worker-detail.component.html',
})
export class WorkerDetailComponent {
  readonly id = input.required<string>();

  private readonly workers = inject(WorkerService);
  protected readonly auth = inject(AuthService);
  private readonly notifications = inject(NotificationService);

  protected readonly worker = signal<User | null>(null);
  protected readonly loading = signal(false);
  protected readonly errorMessage = signal<string | null>(null);

  protected readonly activeTab = signal<WorkerTab>('datos');
  protected readonly tabs = computed<{ key: WorkerTab; label: string }[]>(() => {
    const tabs: { key: WorkerTab; label: string }[] = [{ key: 'datos', label: 'Datos y acceso' }];
    if (this.auth.hasPermission('worker_schedules.view')) {
      tabs.push({ key: 'horario', label: 'Horario' });
    }
    return tabs;
  });

  protected readonly submittingActive = signal(false);

  constructor() {
    effect(() => {
      const workerId = Number(this.id());
      if (!Number.isInteger(workerId) || workerId <= 0) {
        this.errorMessage.set('El identificador del trabajador no es válido.');
        return;
      }
      void this.load(workerId);
    });
  }

  private async load(workerId: number): Promise<void> {
    this.loading.set(true);
    this.errorMessage.set(null);
    try {
      const worker = await this.workers.getById(workerId);
      this.worker.set(worker);
    } catch {
      this.errorMessage.set(loadResourceError('el trabajador'));
    } finally {
      this.loading.set(false);
    }
  }

  protected setActiveTab(tab: WorkerTab): void {
    this.activeTab.set(tab);
  }

  protected onWorkerUpdated(worker: User): void {
    this.worker.set(worker);
  }

  async toggleActive(): Promise<void> {
    const current = this.worker();
    if (current === null || this.submittingActive()) return;

    const confirmed = await this.notifications.modal.confirm({
      variant: current.is_active ? 'warning' : 'info',
      title: current.is_active ? '¿Desactivar al trabajador?' : '¿Reactivar al trabajador?',
      message: current.is_active
        ? `El trabajador "${current.name}" no podrá acceder al panel. Su historial se conserva.`
        : `Se restaurará el acceso del trabajador "${current.name}".`,
      confirmText: current.is_active ? 'Sí, desactivar' : 'Sí, reactivar',
      cancelText: 'Cancelar',
    });
    if (!confirmed) return;

    this.submittingActive.set(true);
    try {
      if (current.is_active) {
        await this.workers.deactivate(current.id);
        this.worker.set({ ...current, is_active: false });
      } else {
        const updated = await this.workers.activate(current.id);
        this.worker.set(updated);
      }
      this.notifications.toast.success(
        current.is_active ? 'Trabajador desactivado.' : 'Trabajador reactivado.',
      );
    } catch (error) {
      const message = (error as HttpErrorResponse).error?.message ?? GENERIC_ERROR;
      this.notifications.toast.error(message);
    } finally {
      this.submittingActive.set(false);
    }
  }
}
