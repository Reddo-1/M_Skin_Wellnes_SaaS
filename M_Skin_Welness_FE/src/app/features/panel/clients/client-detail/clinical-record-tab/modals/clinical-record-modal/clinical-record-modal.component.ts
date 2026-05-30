import { Component, computed, effect, inject, input, output } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ClinicalRecordSummary } from '../../../../../../../core/models/clinical-record.model';
import { BodyType } from '../../../../../../../core/models/skin-evaluation.model';
import { LookupService } from '../../../../../../../core/services/lookup.service';
import { hasFieldError, hasValidationError } from '../../../../../../../core/utils/form.util';
import { ModalComponent } from '../../../../../../../shared/ui/modal/modal.component';
import { MultiSelectComponent } from '../../../../../../../shared/ui/multi-select/multi-select.component';
import { SelectComponent, SelectOption } from '../../../../../../../shared/ui/select/select.component';
import { TextareaComponent } from '../../../../../../../shared/ui/textarea/textarea.component';
import { DatePickerComponent } from '../../../../../../../shared/ui/date-picker/date-picker.component';

export interface ClinicalRecordEvaluationValue {
  skin_type_id: number;
  evaluation_date: string;
  general_notes: string;
  variation_ids: number[];
}

export interface ClinicalRecordFormValue {
  general_notes: string;
  evaluation: ClinicalRecordEvaluationValue | null;
}

type RecordField = 'general_notes' | 'skin_type_id' | 'evaluation_date' | 'evaluation_notes';

@Component({
  selector: 'app-clinical-record-modal',
  standalone: true,
  imports: [
    ReactiveFormsModule,
    ModalComponent,
    MultiSelectComponent,
    SelectComponent,
    TextareaComponent,
    DatePickerComponent,
  ],
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
  protected readonly lookup = inject(LookupService);

  protected readonly isEdit = computed(() => this.record() !== null);

  protected readonly skinTypeOptions = computed<SelectOption[]>(() =>
    this.lookup.skinTypes().map((type) => ({ value: String(type.id), label: type.name })),
  );

  protected readonly form = this.fb.nonNullable.group({
    general_notes: ['', [Validators.maxLength(5000)]],
    skin_type_id: [''],
    evaluation_date: [this.today()],
    evaluation_notes: ['', [Validators.maxLength(5000)]],
    variation_ids: this.fb.nonNullable.control<number[]>([]),
  });

  constructor() {
    effect(() => {
      if (!this.isOpen()) return;
      const current = this.record();
      this.form.reset({
        general_notes: current?.general_notes ?? '',
        skin_type_id: '',
        evaluation_date: this.today(),
        evaluation_notes: '',
        variation_ids: [],
      });

      //la primera evaluacion solo se pide al crear la ficha; en edicion solo se tocan las notas
      const skinTypeControl = this.form.controls.skin_type_id;
      const dateControl = this.form.controls.evaluation_date;
      if (current === null) {
        skinTypeControl.setValidators([Validators.required]);
        dateControl.setValidators([Validators.required]);
      } else {
        skinTypeControl.clearValidators();
        dateControl.clearValidators();
      }
      skinTypeControl.updateValueAndValidity({ emitEvent: false });
      dateControl.updateValueAndValidity({ emitEvent: false });
    });
  }

  protected onSubmit(): void {
    if (this.submitting()) return;
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    const raw = this.form.getRawValue();
    this.formSubmit.emit({
      general_notes: raw.general_notes,
      evaluation: this.isEdit()
        ? null
        : {
            skin_type_id: Number(raw.skin_type_id),
            evaluation_date: raw.evaluation_date,
            general_notes: raw.evaluation_notes,
            variation_ids: raw.variation_ids,
          },
    });
  }

  protected hasFieldError(field: RecordField): boolean {
    return hasFieldError(this.form.controls[field]);
  }

  protected hasValidationError(field: RecordField, key: string): boolean {
    return hasValidationError(this.form.controls[field], key);
  }

  private today(): string {
    const now = new Date();
    const month = `${now.getMonth() + 1}`.padStart(2, '0');
    const day = `${now.getDate()}`.padStart(2, '0');
    return `${now.getFullYear()}-${month}-${day}`;
  }
}
