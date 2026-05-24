import { CommonModule } from '@angular/common';
import { HttpErrorResponse } from '@angular/common/http';
import { Component, computed, inject, input, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { NotificationService } from '../../../core/services/notification.service';
import { ValidationErrorResponse } from '../../../core/models/auth.model';
import { AuthPageLayoutComponent } from '../../../layout/auth-page-layout/auth-page-layout.component';
import { AlertComponent } from '../../../shared/ui/alert/alert.component';

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
  protected readonly generalError = signal<string | null>(null);
  protected readonly fieldErrors = signal<Record<string, string[]>>({});
  private readonly dismissed = signal(false);
  protected readonly showSessionExpired = computed(() => this.sesionExpirada() === '1' && !this.dismissed(),);
  protected readonly showPassword = signal(false);

  protected readonly form = this.fb.nonNullable.group({
    email: ['', [Validators.required, Validators.email]],
    password: ['', [Validators.required, Validators.minLength(4)]],
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
    this.generalError.set(null);
    this.fieldErrors.set({});
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
      this.applyBackendError(error as HttpErrorResponse);
    }
  }

  private applyBackendError(error: HttpErrorResponse): void {
    if (error.status === 422) {
      const body = error.error as ValidationErrorResponse | undefined;
      this.fieldErrors.set(body?.errors ?? {});
      this.generalError.set(null);
      return;
    }

    if (error.status === 0) {
      this.generalError.set(
        'No se puede contactar con el servidor. Comprueba tu conexión e inténtalo de nuevo.',
      );
      return;
    }

    this.generalError.set('Ha ocurrido un error inesperado. Inténtalo de nuevo en unos segundos.');
  }

  protected firstError(field: LoginField): string | null {
    const errors = this.fieldErrors()[field];
    return errors !== undefined && errors.length > 0 ? errors[0] : null;
  }

  protected hasFieldError(field: LoginField): boolean {
    const control = this.form.controls[field];
    return control.touched && (control.invalid || this.firstError(field) !== null);
  }

  protected hasValidationError(field: LoginField, errorKey: string): boolean {
    const control = this.form.controls[field];
    return control.touched && control.errors?.[errorKey] === true;
  }
}

type LoginField = 'email' | 'password';
