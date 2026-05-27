import { Component, computed, effect, inject, input, output } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { hasFieldError, hasValidationError } from '../../../../../core/utils/form.util';
import { BodyType } from '../../../../../core/models/skin-evaluation.model';
import { ModalComponent } from '../../../../../shared/ui/modal/modal.component';

export interface CreateClinicalRecordFormValue {
  general_notes: string;
}

@Component({
  selector: 'app-create-clinical-record-modal',
  standalone: true,
  imports: [ReactiveFormsModule, ModalComponent],
  templateUrl: './create-clinical-record-modal.component.html',
})
export class CreateClinicalRecordModalComponent {
  readonly bodyType = input<BodyType | null>(null);
  readonly submitting = input.required<boolean>();

  readonly close = output<void>();
  readonly formSubmit = output<CreateClinicalRecordFormValue>();

  private readonly fb = inject(FormBuilder);

  protected readonly form = this.fb.nonNullable.group({
    general_notes: ['', [Validators.maxLength(5000)]],
  });

  protected readonly isOpen = computed(() => this.bodyType() !== null);

  constructor() {
    effect(() => {
      if (this.bodyType() !== null) {
        this.form.reset({ general_notes: '' });
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

  protected hasFieldError(field: 'general_notes'): boolean {
    return hasFieldError(this.form.controls[field]);
  }

  protected hasValidationError(field: 'general_notes', key: string): boolean {
    return hasValidationError(this.form.controls[field], key);
  }
}
