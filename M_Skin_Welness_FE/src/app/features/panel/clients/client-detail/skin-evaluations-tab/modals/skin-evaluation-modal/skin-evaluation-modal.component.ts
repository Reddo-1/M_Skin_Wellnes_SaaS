import { Component, computed, effect, inject, input, output } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ClinicalRecordSummary } from '../../../../../../../core/models/clinical-record.model';
import { SkinEvaluationSummary } from '../../../../../../../core/models/skin-evaluation.model';
import { LookupService } from '../../../../../../../core/services/lookup.service';
import { hasFieldError, hasValidationError } from '../../../../../../../core/utils/form.util';
import { ModalComponent } from '../../../../../../../shared/ui/modal/modal.component';
import { MultiSelectComponent } from '../../../../../../../shared/ui/multi-select/multi-select.component';
import { SelectComponent, SelectOption } from '../../../../../../../shared/ui/select/select.component';

export interface SkinEvaluationFormValue {
  client_profile_id: number;
  skin_type_id: number;
  evaluation_date: string;
  general_notes: string | null;
  variation_ids: number[];
}

type EvaluationField = 'client_profile_id' | 'skin_type_id' | 'evaluation_date' | 'general_notes';

@Component({
  selector: 'app-skin-evaluation-modal',
  standalone: true,
  imports: [ReactiveFormsModule, ModalComponent, MultiSelectComponent, SelectComponent],
  templateUrl: './skin-evaluation-modal.component.html',
})
export class SkinEvaluationModalComponent {
  readonly isOpen = input.required<boolean>();
  readonly evaluation = input<SkinEvaluationSummary | null>(null);
  readonly records = input<ClinicalRecordSummary[]>([]);
  readonly submitting = input.required<boolean>();

  readonly close = output<void>();
  readonly formSubmit = output<SkinEvaluationFormValue>();

  private readonly fb = inject(FormBuilder);
  protected readonly lookup = inject(LookupService);

  protected readonly isEdit = computed(() => this.evaluation() !== null);

  protected readonly recordOptions = computed<SelectOption[]>(() =>
    this.records().map((record) => ({
      value: String(record.id),
      label: record.body_type === 'facial' ? 'Ficha facial' : 'Ficha corporal',
    })),
  );

  protected readonly skinTypeOptions = computed<SelectOption[]>(() =>
    this.lookup.skinTypes().map((type) => ({ value: String(type.id), label: type.name })),
  );

  protected readonly form = this.fb.nonNullable.group({
    client_profile_id: ['', Validators.required],
    skin_type_id: ['', Validators.required],
    evaluation_date: [this.today(), Validators.required],
    general_notes: ['', Validators.maxLength(5000)],
    variation_ids: this.fb.nonNullable.control<number[]>([]),
  });

  constructor() {
    effect(() => {
      if (!this.isOpen()) return;
      const current = this.evaluation();
      this.form.reset({
        client_profile_id: current !== null ? String(current.client_profile_id) : '',
        skin_type_id: current !== null ? String(current.skin_type_id) : '',
        evaluation_date: current?.evaluation_date ?? this.today(),
        general_notes: current?.general_notes ?? '',
        variation_ids: current?.variations?.map((variation) => variation.id) ?? [],
      });
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
      client_profile_id: Number(raw.client_profile_id),
      skin_type_id: Number(raw.skin_type_id),
      evaluation_date: raw.evaluation_date,
      general_notes: raw.general_notes.trim() === '' ? null : raw.general_notes,
      variation_ids: raw.variation_ids,
    });
  }

  protected hasFieldError(field: EvaluationField): boolean {
    return hasFieldError(this.form.controls[field]);
  }

  protected hasValidationError(field: EvaluationField, key: string): boolean {
    return hasValidationError(this.form.controls[field], key);
  }

  private today(): string {
    const now = new Date();
    const month = `${now.getMonth() + 1}`.padStart(2, '0');
    const day = `${now.getDate()}`.padStart(2, '0');
    return `${now.getFullYear()}-${month}-${day}`;
  }
}
