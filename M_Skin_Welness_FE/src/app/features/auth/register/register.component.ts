import { CurrencyPipe } from '@angular/common';
import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { NotificationService } from '../../../core/services/notification.service';
import { PublicPlan, RegistrationService } from '../../../core/services/registration.service';
import { apiError, hasFieldError, hasValidationError, loadResourceError } from '../../../core/utils/form.util';
import { AuthPageLayoutComponent } from '../../../layout/auth-page-layout/auth-page-layout.component';
import { AlertComponent } from '../../../shared/ui/alert/alert.component';
import { InputComponent } from '../../../shared/ui/input/input.component';

type RegisterField =
  | 'centerName'
  | 'centerSlug'
  | 'adminName'
  | 'adminEmail'
  | 'password'
  | 'passwordConfirmation';

@Component({
  selector: 'app-register',
  standalone: true,
  imports: [
    ReactiveFormsModule,
    RouterLink,
    CurrencyPipe,
    AuthPageLayoutComponent,
    AlertComponent,
    InputComponent,
  ],
  templateUrl: './register.component.html',
})
export class RegisterComponent {
  private readonly fb = inject(FormBuilder);
  private readonly registration = inject(RegistrationService);
  private readonly notifications = inject(NotificationService);

  protected readonly plans = signal<PublicPlan[]>([]);
  protected readonly plansError = signal<string | null>(null);
  protected readonly selectedPlanCode = signal<string | null>(null);
  protected readonly submitting = signal(false);

  protected readonly form = this.fb.nonNullable.group({
    centerName: ['', [Validators.required, Validators.maxLength(120)]],
    centerSlug: ['', [Validators.required, Validators.maxLength(80), Validators.pattern(/^[a-z0-9-]+$/)]],
    adminName: ['', [Validators.required, Validators.maxLength(120)]],
    adminEmail: ['', [Validators.required, Validators.email, Validators.maxLength(160)]],
    password: ['', [Validators.required, Validators.minLength(8), Validators.maxLength(120)]],
    passwordConfirmation: ['', [Validators.required]],
  });

  constructor() {
    void this.loadPlans();
  }

  protected selectPlan(code: string): void {
    this.selectedPlanCode.set(code);
  }

  protected planCardClass(code: string): string {
    return this.selectedPlanCode() === code
      ? 'border-brand-500 bg-brand-50'
      : 'border-gray-200 hover:border-gray-300';
  }

  protected showPasswordMismatch(): boolean {
    const password = this.form.controls.password;
    const confirmation = this.form.controls.passwordConfirmation;
    return confirmation.touched && confirmation.value !== '' && password.value !== confirmation.value;
  }

  protected hasFieldError(field: RegisterField): boolean {
    return hasFieldError(this.form.controls[field]);
  }

  protected hasValidationError(field: RegisterField, key: string): boolean {
    return hasValidationError(this.form.controls[field], key);
  }

  async submit(): Promise<void> {
    const planCode = this.selectedPlanCode();
    if (this.form.invalid || planCode === null || this.showPasswordMismatch() || this.submitting()) {
      this.form.markAllAsTouched();
      return;
    }

    this.submitting.set(true);
    const value = this.form.getRawValue();
    try {
      const checkoutUrl = await this.registration.register({
        admin: {
          name: value.adminName,
          email: value.adminEmail,
          password: value.password,
          password_confirmation: value.passwordConfirmation,
        },
        center: { name: value.centerName, slug: value.centerSlug },
        plan_code: planCode,
      });
      window.location.href = checkoutUrl;
    } catch (error) {
      this.submitting.set(false);
      this.notifications.toast.error(apiError(error));
    }
  }

  private async loadPlans(): Promise<void> {
    this.plansError.set(null);
    try {
      const plans = await this.registration.publicPlans();
      this.plans.set(plans);
      this.selectedPlanCode.set(plans[1]?.code ?? plans[0]?.code ?? null);
    } catch {
      this.plansError.set(loadResourceError('los planes'));
    }
  }
}
