import { Component, computed, effect, inject, input, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { DatePipe } from '@angular/common';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { HttpErrorResponse } from '@angular/common/http';
import { WorkerService, UpdateWorkerData } from '../../../../core/services/worker.service';
import { AuthService } from '../../../../core/services/auth.service';
import { LookupService } from '../../../../core/services/lookup.service';
import { NotificationService } from '../../../../core/services/notification.service';
import { User } from '../../../../core/models/user.model';
import { LookupItem } from '../../../../core/models/lookup.model';
import { GENERIC_ERROR, hasFieldError, hasValidationError, loadResourceError } from '../../../../core/utils/form.util';
import { AlertComponent } from '../../../../shared/ui/alert/alert.component';
import { MultiSelectComponent } from '../../../../shared/ui/multi-select/multi-select.component';
import { InputComponent } from '../../../../shared/ui/input/input.component';
import { WorkerModalComponent, WorkerFormValue } from '../modals/worker-modal/worker-modal.component';
import { ScheduleTabComponent } from './schedule-tab/schedule-tab.component';

type WorkerTab = 'datos' | 'horario';

@Component({
  selector: 'app-worker-detail',
  standalone: true,
  imports: [
    RouterLink,
    DatePipe,
    ReactiveFormsModule,
    AlertComponent,
    MultiSelectComponent,
    InputComponent,
    WorkerModalComponent,
    ScheduleTabComponent,
  ],
  templateUrl: './worker-detail.component.html',
})
export class WorkerDetailComponent {
  readonly id = input.required<string>();

  private readonly workers = inject(WorkerService);
  protected readonly auth = inject(AuthService);
  private readonly lookups = inject(LookupService);
  private readonly notifications = inject(NotificationService);
  private readonly fb = inject(FormBuilder);

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

  protected readonly modalOpen = signal(false);
  protected readonly submitting = signal(false);

  protected readonly selectedRoleIds = signal<number[]>([]);
  protected readonly submittingRoles = signal(false);

  protected readonly submittingPassword = signal(false);
  protected readonly submittingActive = signal(false);

  protected readonly roleOptions = computed<LookupItem[]>(() =>
    this.lookups.roles().filter((role) => role.name !== 'cliente' && role.name !== 'superadmin'),
  );

  protected readonly passwordForm = this.fb.nonNullable.group({
    password: ['', [Validators.required, Validators.minLength(8), Validators.maxLength(255)]],
    password_confirmation: ['', [Validators.required, Validators.minLength(8), Validators.maxLength(255)]],
  });

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
      this.selectedRoleIds.set(this.resolveRoleIds(worker));
    } catch {
      this.errorMessage.set(loadResourceError('el trabajador'));
    } finally {
      this.loading.set(false);
    }
  }

  private resolveRoleIds(worker: User): number[] {
    const byName = new Map(this.lookups.roles().map((role) => [role.name, role.id]));
    return worker.roles
      .map((name) => byName.get(name))
      .filter((id): id is number => id !== undefined);
  }

  protected openEditModal(): void {
    this.modalOpen.set(true);
  }

  protected closeModal(): void {
    if (this.submitting()) return;
    this.modalOpen.set(false);
  }

  protected setActiveTab(tab: WorkerTab): void {
    this.activeTab.set(tab);
  }

  async submitWorker(value: WorkerFormValue): Promise<void> {
    const current = this.worker();
    if (current === null) return;
    const data: UpdateWorkerData = {
      name: value.name,
      email: value.email === '' ? null : value.email,
      phone: value.phone === '' ? null : value.phone,
      birth_date: value.birth_date === '' ? null : value.birth_date,
    };
    this.submitting.set(true);
    try {
      const updated = await this.workers.update(current.id, data);
      this.worker.set(updated);
      this.modalOpen.set(false);
      this.notifications.toast.success('Datos del trabajador actualizados.');
    } catch (error) {
      const message = (error as HttpErrorResponse).error?.message ?? GENERIC_ERROR;
      this.notifications.toast.error(message);
    } finally {
      this.submitting.set(false);
    }
  }

  protected onRolesChange(ids: number[]): void {
    this.selectedRoleIds.set(ids);
  }

  async saveRoles(): Promise<void> {
    const current = this.worker();
    if (current === null) return;
    if (this.selectedRoleIds().length === 0) {
      this.notifications.toast.error('El trabajador debe tener al menos un rol.');
      return;
    }
    this.submittingRoles.set(true);
    try {
      const updated = await this.workers.syncRoles(current.id, this.selectedRoleIds());
      this.worker.set(updated);
      this.selectedRoleIds.set(this.resolveRoleIds(updated));
      this.notifications.toast.success('Roles actualizados.');
    } catch (error) {
      const message = (error as HttpErrorResponse).error?.message ?? GENERIC_ERROR;
      this.notifications.toast.error(message);
    } finally {
      this.submittingRoles.set(false);
    }
  }

  async submitPassword(): Promise<void> {
    const current = this.worker();
    if (current === null) return;
    if (this.passwordForm.invalid) {
      this.passwordForm.markAllAsTouched();
      return;
    }
    const raw = this.passwordForm.getRawValue();
    if (raw.password !== raw.password_confirmation) {
      this.notifications.toast.error('Las contraseñas no coinciden.');
      return;
    }
    this.submittingPassword.set(true);
    try {
      await this.workers.changePassword(current.id, raw.password, raw.password_confirmation);
      this.passwordForm.reset({ password: '', password_confirmation: '' });
      this.notifications.toast.success('Contraseña actualizada.');
    } catch (error) {
      const message = (error as HttpErrorResponse).error?.message ?? GENERIC_ERROR;
      this.notifications.toast.error(message);
    } finally {
      this.submittingPassword.set(false);
    }
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

  protected hasPasswordError(field: 'password' | 'password_confirmation'): boolean {
    return hasFieldError(this.passwordForm.controls[field]);
  }

  protected hasPasswordValidation(field: 'password' | 'password_confirmation', key: string): boolean {
    return hasValidationError(this.passwordForm.controls[field], key);
  }
}
