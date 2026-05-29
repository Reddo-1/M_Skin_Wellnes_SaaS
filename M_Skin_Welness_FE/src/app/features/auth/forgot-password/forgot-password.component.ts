import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { NotificationService } from '../../../core/services/notification.service';
import { HttpErrorResponse } from '@angular/common/http';
import { GENERIC_ERROR, hasFieldError, hasValidationError } from '../../../core/utils/form.util';
import { AuthPageLayoutComponent } from '../../../layout/auth-page-layout/auth-page-layout.component';
import { AlertComponent } from '../../../shared/ui/alert/alert.component';
import { InputComponent } from '../../../shared/ui/input/input.component';

@Component({
  selector: 'app-forgot-password',
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink, AuthPageLayoutComponent, AlertComponent, InputComponent],
  templateUrl: './forgot-password.component.html',
})
export class ForgotPasswordComponent {
  private readonly fb = inject(FormBuilder);
  private readonly auth = inject(AuthService);
  private readonly notifications = inject(NotificationService);

  protected readonly submitting = signal(false);
  protected readonly successMessage = signal<string | null>(null);

  protected readonly form = this.fb.nonNullable.group({
    email: ['', [Validators.required, Validators.email, Validators.maxLength(150)]],
  });

  async submit(): Promise<void> {
    if (this.form.invalid || this.submitting()) {
      this.form.markAllAsTouched();
      return;
    }

    this.submitting.set(true);
    this.successMessage.set(null);

    try {
      const response = await this.auth.forgotPassword(this.form.getRawValue().email);
      this.successMessage.set(response.message);
      this.form.reset({ email: '' });
    } catch (error) {
      const message = (error as HttpErrorResponse).error?.message ?? GENERIC_ERROR;
      this.notifications.toast.error(message);
    } finally {
      this.submitting.set(false);
    }
  }

  protected hasFieldError(): boolean {
    return hasFieldError(this.form.controls.email);
  }

  protected hasValidationError(key: string): boolean {
    return hasValidationError(this.form.controls.email, key);
  }
}
