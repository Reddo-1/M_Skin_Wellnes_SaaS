import { DatePipe } from '@angular/common';
import { Component, computed, effect, inject, input, output, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { WorkerService, UpdateWorkerData } from '../../../../../core/services/worker.service';
import { AuthService } from '../../../../../core/services/auth.service';
import { LookupService } from '../../../../../core/services/lookup.service';
import { NotificationService } from '../../../../../core/services/notification.service';
import { User } from '../../../../../core/models/user.model';
import { LookupItem } from '../../../../../core/models/lookup.model';
import { apiError, hasFieldError, hasValidationError } from '../../../../../core/utils/form.util';
import { MultiSelectComponent } from '../../../../../shared/ui/multi-select/multi-select.component';
import { InputComponent } from '../../../../../shared/ui/input/input.component';
import { WorkerModalComponent, WorkerFormValue } from '../../modals/worker-modal/worker-modal.component';

@Component({
  selector: 'app-worker-data-tab',
  standalone: true,
  imports: [DatePipe, ReactiveFormsModule, MultiSelectComponent, InputComponent, WorkerModalComponent],
  templateUrl: './data-tab.component.html',
})
export class WorkerDataTabComponent {
  readonly worker = input.required<User>();
  readonly updated = output<User>();

  protected readonly auth = inject(AuthService);
  private readonly workers = inject(WorkerService);
  private readonly lookups = inject(LookupService);
  private readonly notifications = inject(NotificationService);
  private readonly fb = inject(FormBuilder);

  protected readonly modalOpen = signal(false);
  protected readonly submitting = signal(false);
  protected readonly selectedRoleIds = signal<number[]>([]);
  protected readonly submittingRoles = signal(false);
  protected readonly submittingPassword = signal(false);

  protected readonly roleOptions = computed<LookupItem[]>(() =>
    this.lookups.roles().filter((role) => role.name !== 'cliente' && role.name !== 'superadmin'),
  );

  protected readonly passwordForm = this.fb.nonNullable.group({
    password: ['', [Validators.required, Validators.minLength(8), Validators.maxLength(255)]],
    password_confirmation: ['', [Validators.required, Validators.minLength(8), Validators.maxLength(255)]],
  });

  constructor() {
    effect(() => {
      this.selectedRoleIds.set(this.resolveRoleIds(this.worker()));
    });
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

  async submitWorker(value: WorkerFormValue): Promise<void> {
    const data: UpdateWorkerData = {
      name: value.name,
      email: value.email === '' ? null : value.email,
      phone: value.phone === '' ? null : value.phone,
      birth_date: value.birth_date === '' ? null : value.birth_date,
    };
    this.submitting.set(true);
    try {
      const updated = await this.workers.update(this.worker().id, data);
      this.updated.emit(updated);
      this.modalOpen.set(false);
      this.notifications.toast.success('Datos del trabajador actualizados.');
    } catch (error) {
      this.notifications.toast.error(apiError(error));
    } finally {
      this.submitting.set(false);
    }
  }

  protected onRolesChange(ids: number[]): void {
    this.selectedRoleIds.set(ids);
  }

  async saveRoles(): Promise<void> {
    if (this.selectedRoleIds().length === 0) {
      this.notifications.toast.error('El trabajador debe tener al menos un rol.');
      return;
    }
    this.submittingRoles.set(true);
    try {
      const updated = await this.workers.syncRoles(this.worker().id, this.selectedRoleIds());
      this.updated.emit(updated);
      this.selectedRoleIds.set(this.resolveRoleIds(updated));
      this.notifications.toast.success('Roles actualizados.');
    } catch (error) {
      this.notifications.toast.error(apiError(error));
    } finally {
      this.submittingRoles.set(false);
    }
  }

  async submitPassword(): Promise<void> {
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
      await this.workers.changePassword(this.worker().id, raw.password, raw.password_confirmation);
      this.passwordForm.reset({ password: '', password_confirmation: '' });
      this.notifications.toast.success('Contraseña actualizada.');
    } catch (error) {
      this.notifications.toast.error(apiError(error));
    } finally {
      this.submittingPassword.set(false);
    }
  }

  protected hasPasswordError(field: 'password' | 'password_confirmation'): boolean {
    return hasFieldError(this.passwordForm.controls[field]);
  }

  protected hasPasswordValidation(field: 'password' | 'password_confirmation', key: string): boolean {
    return hasValidationError(this.passwordForm.controls[field], key);
  }
}
