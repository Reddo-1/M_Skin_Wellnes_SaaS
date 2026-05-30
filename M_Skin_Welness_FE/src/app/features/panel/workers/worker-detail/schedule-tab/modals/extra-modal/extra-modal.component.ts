import { Component, computed, effect, inject, input, output } from '@angular/core';
import { AbstractControl, FormBuilder, ReactiveFormsModule, ValidationErrors, Validators } from '@angular/forms';
import { WorkerExtraAvailability } from '../../../../../../../core/models/worker-extra-availability.model';
import { hasFieldError, hasValidationError } from '../../../../../../../core/utils/form.util';
import { ModalComponent } from '../../../../../../../shared/ui/modal/modal.component';
import { DatePickerComponent } from '../../../../../../../shared/ui/date-picker/date-picker.component';
import { TimePickerComponent } from '../../../../../../../shared/ui/time-picker/time-picker.component';
import { InputComponent } from '../../../../../../../shared/ui/input/input.component';

export interface ExtraFormValue {
  date: string;
  start_time: string;
  end_time: string;
  reason: string | null;
}

const endAfterStartValidator = (group: AbstractControl): ValidationErrors | null => {
  const start = group.get('start_time')?.value;
  const end = group.get('end_time')?.value;
  if (start && end && end <= start) {
    return { endBeforeStart: true };
  }
  return null;
};

@Component({
  selector: 'app-extra-modal',
  standalone: true,
  imports: [ReactiveFormsModule, ModalComponent, DatePickerComponent, TimePickerComponent, InputComponent],
  templateUrl: './extra-modal.component.html',
})
export class ExtraModalComponent {
  readonly isOpen = input.required<boolean>();
  readonly extra = input<WorkerExtraAvailability | null>(null);
  readonly submitting = input.required<boolean>();

  readonly close = output<void>();
  readonly formSubmit = output<ExtraFormValue>();
  readonly remove = output<void>();

  private readonly fb = inject(FormBuilder);

  protected readonly isEdit = computed(() => this.extra() !== null);

  protected readonly form = this.fb.nonNullable.group(
    {
      date: ['', [Validators.required]],
      start_time: ['', [Validators.required]],
      end_time: ['', [Validators.required]],
      reason: ['', [Validators.maxLength(120)]],
    },
    { validators: endAfterStartValidator },
  );

  constructor() {
    effect(() => {
      if (!this.isOpen()) return;
      const current = this.extra();
      this.form.reset({
        date: current?.date ?? '',
        start_time: current ? current.start_time.slice(0, 5) : '',
        end_time: current ? current.end_time.slice(0, 5) : '',
        reason: current?.reason ?? '',
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
      date: raw.date,
      start_time: raw.start_time,
      end_time: raw.end_time,
      reason: raw.reason.trim() === '' ? null : raw.reason.trim(),
    });
  }

  protected hasFieldError(field: 'date' | 'start_time' | 'end_time'): boolean {
    return hasFieldError(this.form.controls[field]);
  }

  protected hasValidation(key: string): boolean {
    return hasValidationError(this.form.controls.reason, key);
  }

  protected showEndBeforeStart(): boolean {
    return this.form.controls.end_time.touched && this.form.hasError('endBeforeStart');
  }
}
