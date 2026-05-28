import { Component, computed, effect, inject, input, output } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ClinicalRecordSummary } from '../../../../../../../core/models/clinical-record.model';
import { hasFieldError, hasValidationError } from '../../../../../../../core/utils/form.util';
import { ModalComponent } from '../../../../../../../shared/ui/modal/modal.component';

export interface EditClinicalRecordFormValue {
  general_notes: string;
}

@Component({
  selector: 'app-edit-clinical-record-modal',
  standalone: true,
  imports: [ReactiveFormsModule, ModalComponent],
  templateUrl: './edit-clinical-record-modal.component.html',
})
export class EditClinicalRecordModalComponent {
  readonly record = input<ClinicalRecordSummary | null>(null);
  readonly submitting = input.required<boolean>();

  readonly close = output<void>();
  readonly formSubmit = output<EditClinicalRecordFormValue>();

  private readonly fb = inject(FormBuilder);

  protected readonly form = this.fb.nonNullable.group({
    general_notes: ['', [Validators.maxLength(5000)]],
  });

  protected readonly isOpen = computed(() => this.record() !== null);

  constructor() {
    effect(() => {
      const record = this.record();
      if (record !== null) {
        this.form.reset({ general_notes: record.general_notes ?? '' });
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
