import { Component, computed, effect, inject, input, output } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { User } from '../../../../../core/models/user.model';
import { hasFieldError, hasValidationError } from '../../../../../core/utils/form.util';
import { ModalComponent } from '../../../../../shared/ui/modal/modal.component';

export interface ActivateOnlineFormValue {
  email: string | null;
}

@Component({
  selector: 'app-activate-online-modal',
  standalone: true,
  imports: [ReactiveFormsModule, ModalComponent],
  templateUrl: './activate-online-modal.component.html',
})
export class ActivateOnlineModalComponent {
  readonly client = input<User | null>(null);
  readonly submitting = input.required<boolean>();

  readonly close = output<void>();
  readonly formSubmit = output<ActivateOnlineFormValue>();

  private readonly fb = inject(FormBuilder);

  protected readonly emailRequired = computed(() => this.client()?.email === null);

  protected readonly form = this.fb.nonNullable.group({
    email: ['', [Validators.email, Validators.maxLength(150)]],
  });

  constructor() {
    effect(() => {
      const client = this.client();
      if (client === null) return;

      this.form.reset({ email: '' });

      if (client.email === null) {
        this.form.controls.email.setValidators([
          Validators.required,
          Validators.email,
          Validators.maxLength(150),
        ]);
      } else {
        this.form.controls.email.setValidators([Validators.email, Validators.maxLength(150)]);
      }
      this.form.controls.email.updateValueAndValidity({ emitEvent: false });
    });
  }

  protected onSubmit(): void {
    if (this.submitting()) return;
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    const raw = this.form.getRawValue();
    this.formSubmit.emit({ email: raw.email === '' ? null : raw.email });
  }

  protected hasFieldError(): boolean {
    return hasFieldError(this.form.controls.email);
  }

  protected hasValidationError(key: string): boolean {
    return hasValidationError(this.form.controls.email, key);
  }
}
