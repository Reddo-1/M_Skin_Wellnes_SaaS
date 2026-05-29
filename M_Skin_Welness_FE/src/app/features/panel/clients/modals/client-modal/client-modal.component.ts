import { Component, computed, effect, inject, input, output } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { User } from '../../../../../core/models/user.model';
import { hasFieldError, hasValidationError } from '../../../../../core/utils/form.util';
import { ModalComponent } from '../../../../../shared/ui/modal/modal.component';

export interface ClientFormValue {
  name: string;
  email: string;
  phone: string;
  birth_date: string;
}

type ClientField = 'name' | 'email' | 'phone' | 'birth_date';

@Component({
  selector: 'app-client-modal',
  standalone: true,
  imports: [ReactiveFormsModule, ModalComponent],
  templateUrl: './client-modal.component.html',
})
export class ClientModalComponent {
  readonly isOpen = input.required<boolean>();
  readonly client = input<User | null>(null);
  readonly submitting = input.required<boolean>();

  readonly close = output<void>();
  readonly formSubmit = output<ClientFormValue>();

  private readonly fb = inject(FormBuilder);

  protected readonly isEdit = computed(() => this.client() !== null);

  protected readonly form = this.fb.nonNullable.group({
    name: ['', [Validators.required, Validators.maxLength(120)]],
    email: ['', [Validators.email, Validators.maxLength(150)]],
    phone: ['', [Validators.maxLength(30)]],
    birth_date: [''],
  });

  constructor() {
    effect(() => {
      if (!this.isOpen()) return;
      const current = this.client();
      this.form.reset({
        name: current?.name ?? '',
        email: current?.email ?? '',
        phone: current?.phone ?? '',
        birth_date: current?.birth_date ?? '',
      });
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

  protected hasFieldError(field: ClientField): boolean {
    return hasFieldError(this.form.controls[field]);
  }

  protected hasValidationError(field: ClientField, key: string): boolean {
    return hasValidationError(this.form.controls[field], key);
  }
}
