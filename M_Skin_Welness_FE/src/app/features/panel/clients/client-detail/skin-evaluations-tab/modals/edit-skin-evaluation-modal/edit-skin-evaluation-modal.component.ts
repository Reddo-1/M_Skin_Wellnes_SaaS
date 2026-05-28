import { Component, computed, effect, inject, input, output } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { SkinEvaluationSummary } from '../../../../../../../core/models/skin-evaluation.model';
import { LookupService } from '../../../../../../../core/services/lookup.service';
import { hasFieldError, hasValidationError } from '../../../../../../../core/utils/form.util';
import { ModalComponent } from '../../../../../../../shared/ui/modal/modal.component';
import { MultiSelectComponent } from '../../../../../../../shared/ui/multi-select/multi-select.component';
import { SelectComponent, SelectOption } from '../../../../../../../shared/ui/select/select.component';

export interface EditSkinEvaluationFormValue {
  skin_type_id: number;
  evaluation_date: string;
  general_notes: string | null;
  variation_ids: number[];
}

type EvaluationField = 'skin_type_id' | 'evaluation_date' | 'general_notes';

@Component({
  selector: 'app-edit-skin-evaluation-modal',
  standalone: true,
  imports: [ReactiveFormsModule, ModalComponent, MultiSelectComponent, SelectComponent],
  templateUrl: './edit-skin-evaluation-modal.component.html',
})
export class EditSkinEvaluationModalComponent {
  readonly evaluation = input<SkinEvaluationSummary | null>(null);
  readonly submitting = input.required<boolean>();

  readonly close = output<void>();
  readonly formSubmit = output<EditSkinEvaluationFormValue>();

  private readonly fb = inject(FormBuilder);
  protected readonly lookup = inject(LookupService);

  protected readonly skinTypeOptions = computed<SelectOption[]>(() =>
    this.lookup.skinTypes().map((type) => ({ value: String(type.id), label: type.name })),
  );

  protected readonly form = this.fb.nonNullable.group({
    skin_type_id: ['', Validators.required],
    evaluation_date: ['', Validators.required],
    general_notes: ['', Validators.maxLength(5000)],
    variation_ids: this.fb.nonNullable.control<number[]>([]),
  });

  protected readonly isOpen = computed(() => this.evaluation() !== null);

  constructor() {
    effect(() => {
      const evaluation = this.evaluation();
      if (evaluation !== null) {
        this.form.reset({
          skin_type_id: String(evaluation.skin_type_id),
          evaluation_date: evaluation.evaluation_date ?? this.today(),
          general_notes: evaluation.general_notes ?? '',
          variation_ids: evaluation.variations?.map((variation) => variation.id) ?? [],
        });
      }
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
