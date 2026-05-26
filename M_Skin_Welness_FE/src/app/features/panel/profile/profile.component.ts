import { CommonModule } from '@angular/common';
import { HttpErrorResponse } from '@angular/common/http';
import { Component, computed, inject, signal, viewChild, ElementRef, WritableSignal } from '@angular/core';
import { AbstractControl, FormBuilder, ReactiveFormsModule, ValidationErrors, ValidatorFn, Validators } from '@angular/forms';
import { AuthService } from '../../../core/services/auth.service';
import { NotificationService } from '../../../core/services/notification.service';
import { UserService } from '../../../core/services/user.service';
import { ValidationErrorResponse } from '../../../core/models/auth.model';
import { ROLE_LABELS } from '../../../../environments/environment';
import { AlertComponent } from '../../../shared/ui/alert/alert.component';
import { IconComponent } from '../../../shared/ui/icon/icon.component';
import { ModalComponent } from '../../../shared/ui/modal/modal.component';

const passwordsMatchValidator: ValidatorFn = (group: AbstractControl): ValidationErrors | null => {
  const password = group.get('password')?.value;
  const confirmation = group.get('password_confirmation')?.value;
  if (password && confirmation && password !== confirmation) {
    return { passwordsMismatch: true };
  }
  return null;
};

type PersonalField = 'name' | 'email' | 'phone' | 'birth_date';
type PasswordField = 'password' | 'password_confirmation';

@Component({
  selector: 'app-profile',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, AlertComponent, IconComponent, ModalComponent],
  templateUrl: './profile.component.html',
})
export class ProfileComponent {
  protected readonly auth = inject(AuthService);
  private readonly users = inject(UserService);
  private readonly notifications = inject(NotificationService);
  private readonly fb = inject(FormBuilder);

  private readonly avatarInput = viewChild<ElementRef<HTMLInputElement>>('avatarInput');

  protected readonly editModalOpen = signal(false);
  protected readonly savingPersonal = signal(false);
  protected readonly changingPassword = signal(false);
  protected readonly uploadingAvatar = signal(false);

  protected readonly personalFieldErrors = signal<Record<string, string[]>>({});
  protected readonly passwordFieldErrors = signal<Record<string, string[]>>({});
  protected readonly personalGeneralError = signal<string | null>(null);
  protected readonly passwordGeneralError = signal<string | null>(null);

  //Linea de roles ej: Administrador · Dermoesteticién · Manicurista etc.
  protected readonly rolesLabel = computed(() => {
    const roles = this.auth.user()?.roles ?? [];
    if (roles.length === 0) return '';
    return roles
      .map((role) => ROLE_LABELS.find((entry) => entry.code === role)?.label ?? role)
      .join(' · ');
  });

  protected readonly personalForm = this.fb.nonNullable.group({
    name: [this.auth.user()?.name ?? '', [Validators.required, Validators.maxLength(120)]],
    email: [this.auth.user()?.email ?? '', [Validators.required, Validators.email, Validators.maxLength(150)]],
    phone: this.fb.control<string | null>(this.auth.user()?.phone ?? null, [Validators.maxLength(30)]),
    birth_date: this.fb.control<string | null>(this.auth.user()?.birth_date ?? null),
  });

  protected readonly passwordForm = this.fb.nonNullable.group(
    {
      password: ['', [Validators.required, Validators.minLength(8), Validators.maxLength(255)]],
      password_confirmation: ['', Validators.required],
    },
    { validators: passwordsMatchValidator },
  );

  protected openEditModal(): void {
    if (this.auth.user() === null) return;
    this.personalForm.reset({
      name: this.auth.user()?.name,
      email: this.auth.user()?.email,
      phone: this.auth.user()?.phone,
      birth_date: this.auth.user()?.birth_date,
    });
    this.personalFieldErrors.set({});
    this.personalGeneralError.set(null);
    this.editModalOpen.set(true);
  }

  protected closeEditModal(): void {
    if (this.savingPersonal()) return;
    this.editModalOpen.set(false);
  }

  async submitPersonal(): Promise<void> {
    if (this.personalForm.invalid || this.savingPersonal()) {
      this.personalForm.markAllAsTouched();
      return;
    }
    const user = this.auth.user();
    if (user === null) return;

    this.savingPersonal.set(true);
    this.personalFieldErrors.set({});
    this.personalGeneralError.set(null);

    try {
      const raw = this.personalForm.getRawValue();
      await this.users.update(user.id, {
        name: raw.name,
        email: raw.email,
        phone: raw.phone === '' ? null : raw.phone,
        birth_date: raw.birth_date === '' ? null : raw.birth_date,
      });
      await this.auth.fetchMe();
      this.editModalOpen.set(false);
      this.notifications.toast.success('Datos personales actualizados.');
    } catch (error) {
      this.applyBackendError(error as HttpErrorResponse, this.personalFieldErrors, this.personalGeneralError);
    } finally {
      this.savingPersonal.set(false);
    }
  }

  async submitPassword(): Promise<void> {
    if (this.passwordForm.invalid || this.changingPassword()) {
      this.passwordForm.markAllAsTouched();
      return;
    }
    const user = this.auth.user();
    if (user === null) return;

    const confirmed = await this.notifications.modal.confirm({
      variant: 'warning',
      title: '¿Cambiar tu contraseña?',
      message: 'Esta acción es irreversible.',
      confirmText: 'Sí, cambiar',
      cancelText: 'Cancelar',
    });
    if (!confirmed) return;

    this.changingPassword.set(true);
    this.passwordFieldErrors.set({});
    this.passwordGeneralError.set(null);

    try {
      const raw = this.passwordForm.getRawValue();
      await this.users.changePassword(user.id, raw.password, raw.password_confirmation);
      this.passwordForm.reset({ password: '', password_confirmation: '' });
      this.notifications.toast.success('Contraseña actualizada.');
    } catch (error) {
      this.applyBackendError(error as HttpErrorResponse, this.passwordFieldErrors, this.passwordGeneralError);
    } finally {
      this.changingPassword.set(false);
    }
  }

  //Hace que el click al botón actue como click al input tipo redirección. Por estilo.
  protected triggerAvatarPicker(): void {
    this.avatarInput()?.nativeElement.click();
  }

  //Validación de la imagen nada más cambiarla.
  async onAvatarSelected(event: Event): Promise<void> {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    input.value = '';
    if (!file) return;

    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
      this.notifications.toast.error('La foto debe ser un archivo JPG, PNG o WEBP.');
      return;
    }
    if (file.size > 5 * 1024 * 1024) {
      this.notifications.toast.error('La foto supera el tamaño máximo de 5 MB.');
      return;
    }

    const user = this.auth.user();
    if (user === null) return;

    const confirmed = await this.notifications.modal.confirm({
      variant: 'info',
      title: '¿Actualizar tu foto de perfil?',
      message: `Se subirá "${file.name}" como tu nueva foto. La anterior se reemplazará.`,
      confirmText: 'Sí, subir',
      cancelText: 'Cancelar',
    });
    if (!confirmed) return;

    this.uploadingAvatar.set(true);
    try {
      await this.users.uploadAvatar(user.id, file);
      await this.auth.fetchMe();
      this.notifications.toast.success('Foto de perfil actualizada.');
    } catch (error) {
      const httpError = error as HttpErrorResponse;
      const errorMsg = httpError.status === 422
        ? Object.values((httpError.error as ValidationErrorResponse | undefined)?.errors ?? {})[0]?.[0]
        : httpError.status === 413
          ? 'La imagen es demasiado grande. El máximo permitido son 5 MB.'
          : undefined;
      this.notifications.toast.error(errorMsg ?? 'No se ha podido actualizar la foto de perfil.');
    } finally {
      this.uploadingAvatar.set(false);
    }
  }

  //Errores cambiar clase de inputs (pintar borde en rojo); devuelven boolean 
  protected hasPersonalError(field: PersonalField): boolean {
    const control = this.personalForm.controls[field];
    return control.touched && (control.invalid || this.personalBackendErrors(field).length > 0);
  }
  protected hasPasswordError(field: PasswordField): boolean {
    const control = this.passwordForm.controls[field];
    return control.touched && (control.invalid || this.passwordBackendErrors(field).length > 0);
  }

  //Pinta errores de BE en los errores de formulario. 
  protected personalBackendErrors(field: PersonalField): string[] {
    return this.personalFieldErrors()[field] ?? [];
  }
  protected passwordBackendErrors(field: PasswordField): string[] {
    return this.passwordFieldErrors()[field] ?? [];
  }

  //Revisa el tipo de validación (key: required,email,minlenth) junto al campo (field: input email,password) para pintar mensajes en html 
  protected hasPersonalValidation(field: PersonalField, key: string): boolean {
    const control = this.personalForm.controls[field];
    return control.touched && control.hasError(key);
  }
  protected hasPasswordValidation(field: PasswordField, key: string): boolean {
    const control = this.passwordForm.controls[field];
    return control.touched && control.hasError(key);
  }

  //Valida los 2 campos de cambio de contraseña y aplica el error al form en sí.
  protected showPasswordMismatch(): boolean {
    return (
      this.passwordForm.controls.password_confirmation.touched &&
      this.passwordForm.errors?.['passwordsMismatch'] === true
    );
  }

  //Pintar bloque de error tipo card si ha habido error de campo desde el BE
  private applyBackendError(
    error: HttpErrorResponse,fieldErrors: WritableSignal<Record<string, string[]>>,generalError: WritableSignal<string | null>,
  ): void {
    if (error.status === 422) {
      const body = error.error as ValidationErrorResponse | undefined;
      fieldErrors.set(body?.errors ?? {});
      generalError.set(null);
      return;
    }
    if (error.status === 0) {
      generalError.set('No se puede contactar con el servidor. Comprueba tu conexión e inténtalo de nuevo.');
      return;
    }
    generalError.set('Ha ocurrido un error inesperado. Inténtalo de nuevo en unos segundos. ');
  }
}
