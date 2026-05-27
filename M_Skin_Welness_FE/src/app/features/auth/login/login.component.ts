import { CommonModule } from '@angular/common';
import { Component, computed, inject, input, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { NotificationService } from '../../../core/services/notification.service';
import { HttpErrorResponse } from '@angular/common/http';
import { GENERIC_ERROR, hasFieldError, hasValidationError } from '../../../core/utils/form.util';
import { AuthPageLayoutComponent } from '../../../layout/auth-page-layout/auth-page-layout.component';
import { AlertComponent } from '../../../shared/ui/alert/alert.component';

type LoginField = 'email' | 'password';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, RouterLink, AuthPageLayoutComponent, AlertComponent],
  templateUrl: './login.component.html',
  styleUrl: './login.component.scss',
})
export class LoginComponent {
  private readonly fb = inject(FormBuilder);
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);
  private readonly notifications = inject(NotificationService);

  readonly sesionExpirada = input<string | undefined>();

  protected readonly submitting = signal(false);
  private readonly dismissed = signal(false);
  protected readonly showSessionExpired = computed(() => this.sesionExpirada() === '1' && !this.dismissed());
  protected readonly showPassword = signal(false);

  protected readonly form = this.fb.nonNullable.group({
    email: ['', [Validators.required, Validators.email]],
    password: ['', [Validators.required]],
  });

  togglePassword(): void {
    this.showPassword.update((value) => !value);
  }

  async submit(): Promise<void> {
    if (this.form.invalid || this.submitting()) {
      this.form.markAllAsTouched();
      return;
    }

    this.submitting.set(true);
    this.dismissed.set(true);

    try {
      const response = await this.auth.login(this.form.getRawValue());
      if (response.user.roles.includes('superadmin')) {
        await this.auth.logout();
        this.submitting.set(false);
        this.notifications.toast.error(
          'Tu cuenta es de superadmin. Accede desde /admin con tu sesión web.',
        );
        return;
      }
      this.router.navigateByUrl(this.auth.defaultRouteForRoles(response.user.roles));
    } catch (error) {
      this.submitting.set(false);
      const message = (error as HttpErrorResponse).error?.message ?? GENERIC_ERROR;
      this.notifications.toast.error(message);
    }
  }

  protected hasFieldError(field: LoginField): boolean {
    return hasFieldError(this.form.controls[field]);
  }

  protected hasValidationError(field: LoginField, key: string): boolean {
    return hasValidationError(this.form.controls[field], key);
  }
}
