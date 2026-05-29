import { Component, computed, effect, inject, input, output } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ClinicalRecordSummary } from '../../../../../../../core/models/clinical-record.model';
import { BodyType } from '../../../../../../../core/models/skin-evaluation.model';
import { hasFieldError, hasValidationError } from '../../../../../../../core/utils/form.util';
import { ModalComponent } from '../../../../../../../shared/ui/modal/modal.component';
import { TextareaComponent } from '../../../../../../../shared/ui/textarea/textarea.component';

export interface ClinicalRecordFormValue {
  general_notes: string;
}

@Component({
  selector: 'app-clinical-record-modal',
  standalone: true,
  imports: [ReactiveFormsModule, ModalComponent, TextareaComponent],
  templateUrl: './clinical-record-modal.component.html',
})
export class ClinicalRecordModalComponent {
  readonly isOpen = input.required<boolean>();
  readonly record = input<ClinicalRecordSummary | null>(null);
  readonly bodyType = input.required<BodyType>();
  readonly submitting = input.required<boolean>();

  readonly close = output<void>();
  readonly formSubmit = output<ClinicalRecordFormValue>();

  private readonly fb = inject(FormBuilder);

  protected readonly isEdit = computed(() => this.record() !== null);

  protected readonly form = this.fb.nonNullable.group({
    general_notes: ['', [Validators.maxLength(5000)]],
  });

  constructor() {
    effect(() => {
      if (!this.isOpen()) return;
      const current = this.record();
      this.form.reset({ general_notes: current?.general_notes ?? '' });
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
