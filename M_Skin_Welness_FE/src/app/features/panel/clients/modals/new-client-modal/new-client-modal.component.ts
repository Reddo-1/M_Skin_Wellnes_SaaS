import { Component, effect, inject, input, output } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { hasFieldError, hasValidationError } from '../../../../../core/utils/form.util';
import { ModalComponent } from '../../../../../shared/ui/modal/modal.component';

export interface NewClientFormValue {
  name: string;
  email: string;
  phone: string;
  birth_date: string;
}

type NewClientField = 'name' | 'email' | 'phone' | 'birth_date';

@Component({
  selector: 'app-new-client-modal',
  standalone: true,
  imports: [ReactiveFormsModule, ModalComponent],
  templateUrl: './new-client-modal.component.html',
})
export class NewClientModalComponent {
  readonly isOpen = input.required<boolean>();
  readonly submitting = input.required<boolean>();

  readonly close = output<void>();
  readonly formSubmit = output<NewClientFormValue>();

  private readonly fb = inject(FormBuilder);

  protected readonly form = this.fb.nonNullable.group({
    name: ['', [Validators.required, Validators.maxLength(120)]],
    email: ['', [Validators.email, Validators.maxLength(150)]],
    phone: ['', [Validators.maxLength(30)]],
    birth_date: [''],
  });

  constructor() {
    effect(() => {
      if (this.isOpen()) {
        this.form.reset({ name: '', email: '', phone: '', birth_date: '' });
      }
    });
  }

  protected onSubmit(): void {
    if (this.submitting()) return;
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    this.formSubmit.emit(this.form.getRawValue());
  }

  protected hasFieldError(field: NewClientField): boolean {
    return hasFieldError(this.form.controls[field]);
  }

  protected hasValidationError(field: NewClientField, key: string): boolean {
    return hasValidationError(this.form.controls[field], key);
  }
}
