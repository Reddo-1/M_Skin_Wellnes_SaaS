import { Component, computed, inject, input, signal } from '@angular/core';
import { AbstractControl, FormBuilder, ReactiveFormsModule, ValidationErrors, ValidatorFn, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { NotificationService } from '../../../core/services/notification.service';
import { HttpErrorResponse } from '@angular/common/http';
import { GENERIC_ERROR, hasFieldError, hasValidationError } from '../../../core/utils/form.util';
import { AuthPageLayoutComponent } from '../../../layout/auth-page-layout/auth-page-layout.component';
import { AlertComponent } from '../../../shared/ui/alert/alert.component';
import { InputComponent } from '../../../shared/ui/input/input.component';

type ResetField = 'password' | 'password_confirmation';

const passwordsMatchValidator: ValidatorFn = (group: AbstractControl): ValidationErrors | null => {
  const password = group.get('password')?.value;
  const confirmation = group.get('password_confirmation')?.value;
  if (password && confirmation && password !== confirmation) {
    return { passwordsMismatch: true };
  }
  return null;
};

@Component({
  selector: 'app-reset-password',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink, AuthPageLayoutComponent, AlertComponent, InputComponent],
  templateUrl: './reset-password.component.html',
})
export class ResetPasswordComponent {
  readonly token = input<string | undefined>();
  readonly email = input<string | undefined>();

  private readonly fb = inject(FormBuilder);
  private readonly auth = inject(AuthService);
  private readonly notifications = inject(NotificationService);
  private readonly router = inject(Router);

  protected readonly submitting = signal(false);

  protected readonly missingParams = computed(() => !this.token() || !this.email());

  protected readonly form = this.fb.nonNullable.group(
    {
      password: ['', [Validators.required, Validators.minLength(8), Validators.maxLength(255)]],
      password_confirmation: ['', [Validators.required, Validators.minLength(8), Validators.maxLength(255)]],
    },
    { validators: passwordsMatchValidator },
  );

  async submit(): Promise<void> {
    if (this.missingParams() || this.submitting()) return;

    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    this.submitting.set(true);

    try {
      const raw = this.form.getRawValue();
      await this.auth.resetPassword({
        token: this.token()!,
        email: this.email()!,
        password: raw.password,
        password_confirmation: raw.password_confirmation,
      });
      this.notifications.toast.success('Contraseña establecida. Ya puedes iniciar sesión.');
      this.router.navigateByUrl('/login');
    } catch (error) {
      const message = (error as HttpErrorResponse).error?.message ?? GENERIC_ERROR;
      this.notifications.toast.error(message);
    } finally {
      this.submitting.set(false);
    }
  }

  protected hasFieldError(field: ResetField): boolean {
    return hasFieldError(this.form.controls[field]);
  }

  protected hasValidationError(field: ResetField, key: string): boolean {
    return hasValidationError(this.form.controls[field], key);
  }

  protected showPasswordMismatch(): boolean {
    return (
      this.form.controls.password_confirmation.touched &&
      this.form.errors?.['passwordsMismatch'] === true
    );
  }
}
