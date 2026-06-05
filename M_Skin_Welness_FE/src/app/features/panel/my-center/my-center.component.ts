import { HttpErrorResponse } from '@angular/common/http';
import { Component, ElementRef, inject, signal, viewChild } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { CenterService } from '../../../core/services/center.service';
import { NotificationService } from '../../../core/services/notification.service';
import { Center } from '../../../core/models/center.model';
import { CenterFile } from '../../../core/models/center-file.model';
import { apiError, hasFieldError, hasValidationError, loadResourceError } from '../../../core/utils/form.util';
import { AlertComponent } from '../../../shared/ui/alert/alert.component';
import { InputComponent } from '../../../shared/ui/input/input.component';
import { ModalComponent } from '../../../shared/ui/modal/modal.component';
import { BooleanPipe } from '../../../shared/pipes/boolean.pipe';

@Component({
  selector: 'app-my-center',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink, AlertComponent, InputComponent, ModalComponent, BooleanPipe],
  templateUrl: './my-center.component.html',
})
export class MyCenterComponent {
  private readonly auth = inject(AuthService);
  private readonly centers = inject(CenterService);
  private readonly notifications = inject(NotificationService);
  private readonly fb = inject(FormBuilder);

  private readonly logoInput = viewChild<ElementRef<HTMLInputElement>>('logoInput');

  protected readonly center = signal<Center | null>(null);
  protected readonly logo = signal<CenterFile | null>(null);
  protected readonly loading = signal(false);
  protected readonly errorMessage = signal<string | null>(null);

  protected readonly editModalOpen = signal(false);
  protected readonly submittingName = signal(false);
  protected readonly submittingLogo = signal(false);

  protected readonly nameForm = this.fb.nonNullable.group({
    name: ['', [Validators.required, Validators.maxLength(120)]],
  });

  constructor() {
    void this.load();
  }

  protected async load(): Promise<void> {
    const centerId = this.auth.user()?.center_id;
    if (centerId == null) {
      this.errorMessage.set('No tienes ningún centro asignado.');
      return;
    }

    this.loading.set(true);
    this.errorMessage.set(null);
    try {
      const [center, logos] = await Promise.all([
        this.centers.show(centerId),
        this.centers.files('logo'),
      ]);
      this.center.set(center);
      this.logo.set(logos[0] ?? null);
    } catch {
      this.errorMessage.set(loadResourceError('los datos del centro'));
    } finally {
      this.loading.set(false);
    }
  }

  protected openEditModal(): void {
    const center = this.center();
    if (center === null) return;
    this.nameForm.reset({ name: center.name });
    this.editModalOpen.set(true);
  }

  protected closeEditModal(): void {
    if (this.submittingName()) return;
    this.editModalOpen.set(false);
  }

  async submitName(): Promise<void> {
    const center = this.center();
    if (center === null) return;
    if (this.nameForm.invalid || this.submittingName()) {
      this.nameForm.markAllAsTouched();
      return;
    }

    this.submittingName.set(true);
    try {
      const updated = await this.centers.update(center.id, { name: this.nameForm.getRawValue().name });
      this.center.set(updated);
      await this.auth.fetchMe();
      this.editModalOpen.set(false);
      this.notifications.toast.success('Datos del centro actualizados.');
    } catch (error) {
      this.notifications.toast.error(apiError(error));
    } finally {
      this.submittingName.set(false);
    }
  }

  protected triggerLogoPicker(): void {
    this.logoInput()?.nativeElement.click();
  }

  async onLogoSelected(event: Event): Promise<void> {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    input.value = '';
    if (!file) return;

    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
      this.notifications.toast.error('El logo debe ser un archivo JPG, PNG o WEBP.');
      return;
    }
    if (file.size > 5 * 1024 * 1024) {
      this.notifications.toast.error('El logo supera el tamaño máximo de 5 MB.');
      return;
    }

    const confirmed = await this.notifications.modal.confirm({
      variant: 'info',
      title: this.logo() ? '¿Actualizar el logo del centro?' : '¿Subir el logo del centro?',
      message: `Se usará "${file.name}" como logo del centro. El anterior se reemplazará.`,
      confirmText: 'Sí, subir',
      cancelText: 'Cancelar',
    });
    if (!confirmed) return;

    this.submittingLogo.set(true);
    try {
      const uploaded = await this.centers.uploadFile('logo', file);
      this.logo.set(uploaded);
      this.notifications.toast.success('Logo actualizado.');
    } catch (error) {
      const httpError = error as HttpErrorResponse;
      const message = httpError.status === 413
        ? 'La imagen es demasiado grande. El máximo permitido son 5 MB.'
        : apiError(error);
      this.notifications.toast.error(message);
    } finally {
      this.submittingLogo.set(false);
    }
  }

  async removeLogo(): Promise<void> {
    const logo = this.logo();
    if (logo === null || this.submittingLogo()) return;

    const confirmed = await this.notifications.modal.confirm({
      variant: 'warning',
      title: '¿Quitar el logo del centro?',
      message: 'El centro dejará de mostrar el logo. Podrás subir uno nuevo cuando quieras.',
      confirmText: 'Sí, quitar',
      cancelText: 'Cancelar',
    });
    if (!confirmed) return;

    this.submittingLogo.set(true);
    try {
      await this.centers.deleteFile(logo.id);
      this.logo.set(null);
      this.notifications.toast.success('Logo eliminado.');
    } catch (error) {
      this.notifications.toast.error(apiError(error));
    } finally {
      this.submittingLogo.set(false);
    }
  }

  protected hasNameError(): boolean {
    return hasFieldError(this.nameForm.controls.name);
  }

  protected hasNameValidation(key: string): boolean {
    return hasValidationError(this.nameForm.controls.name, key);
  }
}
