import { CommonModule, TitleCasePipe } from '@angular/common';
import { HttpErrorResponse } from '@angular/common/http';
import { Component, inject, signal, viewChild, ElementRef } from '@angular/core';
import { AbstractControl, FormBuilder, ReactiveFormsModule, ValidationErrors, ValidatorFn, Validators } from '@angular/forms';
import { AuthService } from '../../../core/services/auth.service';
import { NotificationService } from '../../../core/services/notification.service';
import { UserService } from '../../../core/services/user.service';
import { GENERIC_ERROR, hasFieldError, hasValidationError } from '../../../core/utils/form.util';
import { IconComponent } from '../../../shared/ui/icon/icon.component';
import { ModalComponent } from '../../../shared/ui/modal/modal.component';
import { InputComponent } from '../../../shared/ui/input/input.component';
import { DatePickerComponent } from '../../../shared/ui/date-picker/date-picker.component';

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
  imports: [
    CommonModule,
    ReactiveFormsModule,
    TitleCasePipe,
    IconComponent,
    ModalComponent,
    InputComponent,
    DatePickerComponent,
  ],
  templateUrl: './profile.component.html',
})
export class ProfileComponent {
  protected readonly auth = inject(AuthService);
  private readonly users = inject(UserService);
  private readonly notifications = inject(NotificationService);
  private readonly fb = inject(FormBuilder);

  private readonly avatarInput = viewChild<ElementRef<HTMLInputElement>>('avatarInput');

  protected readonly editModalOpen = signal(false);
  protected readonly submittingPersonal = signal(false);
  protected readonly submittingPassword = signal(false);
  protected readonly submittingAvatar = signal(false);

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
      name: this.auth.user()?.name ?? '',
      email: this.auth.user()?.email ?? '',
      phone: this.auth.user()?.phone ?? null,
      birth_date: this.auth.user()?.birth_date ?? null,
    });
    this.editModalOpen.set(true);
  }

  protected closeEditModal(): void {
    if (this.submittingPersonal()) return;
    this.editModalOpen.set(false);
  }

  async submitPersonal(): Promise<void> {
    if (this.personalForm.invalid || this.submittingPersonal()) {
      this.personalForm.markAllAsTouched();
      return;
    }
    const user = this.auth.user();
    if (user === null) return;

    this.submittingPersonal.set(true);

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
      const message = (error as HttpErrorResponse).error?.message ?? GENERIC_ERROR;
      this.notifications.toast.error(message);
    } finally {
      this.submittingPersonal.set(false);
    }
  }

  async submitPassword(): Promise<void> {
    if (this.passwordForm.invalid || this.submittingPassword()) {
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

    this.submittingPassword.set(true);

    try {
      const raw = this.passwordForm.getRawValue();
      await this.users.changePassword(user.id, raw.password, raw.password_confirmation);
      this.passwordForm.reset({ password: '', password_confirmation: '' });
      this.notifications.toast.success('Contraseña actualizada.');
    } catch (error) {
      const message = (error as HttpErrorResponse).error?.message ?? GENERIC_ERROR;
      this.notifications.toast.error(message);
    } finally {
      this.submittingPassword.set(false);
    }
  }

  protected triggerAvatarPicker(): void {
    this.avatarInput()?.nativeElement.click();
  }

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

    this.submittingAvatar.set(true);
    try {
      await this.users.uploadAvatar(user.id, file);
      await this.auth.fetchMe();
      this.notifications.toast.success('Foto de perfil actualizada.');
    } catch (error) {
      const httpError = error as HttpErrorResponse;
      //413 lo gestionamos aparte: Nginx puede cortar antes de que Laravel responda con su 422
      const message = httpError.status === 413
        ? 'La imagen es demasiado grande. El máximo permitido son 5 MB.'
        : httpError.error?.message ?? 'No se ha podido actualizar la foto de perfil.';
      this.notifications.toast.error(message);
    } finally {
      this.submittingAvatar.set(false);
    }
  }

  protected hasPersonalError(field: PersonalField): boolean {
    return hasFieldError(this.personalForm.controls[field]);
  }

  protected hasPasswordError(field: PasswordField): boolean {
    return hasFieldError(this.passwordForm.controls[field]);
  }

  protected hasPersonalValidation(field: PersonalField, key: string): boolean {
    return hasValidationError(this.personalForm.controls[field], key);
  }

  protected hasPasswordValidation(field: PasswordField, key: string): boolean {
    return hasValidationError(this.passwordForm.controls[field], key);
  }

  protected showPasswordMismatch(): boolean {
    return (
      this.passwordForm.controls.password_confirmation.touched &&
      this.passwordForm.errors?.['passwordsMismatch'] === true
    );
  }
}
