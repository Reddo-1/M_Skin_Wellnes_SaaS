import { Component, computed, effect, inject, input, output, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { AbstractControl, FormBuilder, ReactiveFormsModule, ValidationErrors, Validators } from '@angular/forms';
import { LookupService } from '../../../../../../../core/services/lookup.service';
import { WorkerAbsence } from '../../../../../../../core/models/worker-absence.model';
import { hasFieldError, hasValidationError } from '../../../../../../../core/utils/form.util';
import { ModalComponent } from '../../../../../../../shared/ui/modal/modal.component';
import { SelectComponent, SelectOption } from '../../../../../../../shared/ui/select/select.component';
import { DatePickerComponent } from '../../../../../../../shared/ui/date-picker/date-picker.component';
import { TimePickerComponent } from '../../../../../../../shared/ui/time-picker/time-picker.component';
import { ToggleComponent } from '../../../../../../../shared/ui/toggle/toggle.component';
import { InputComponent } from '../../../../../../../shared/ui/input/input.component';
import { TextareaComponent } from '../../../../../../../shared/ui/textarea/textarea.component';

export interface AbsenceFormValue {
  from: string;
  to: string;
  date: string;
  is_full_day: boolean;
  start_time: string | null;
  end_time: string | null;
  absence_type_id: number | null;
  reason: string | null;
  notes: string | null;
}

const rangeAndTimeValidator = (group: AbstractControl): ValidationErrors | null => {
  const errors: ValidationErrors = {};

  const from = group.get('from')?.value;
  const to = group.get('to')?.value;
  if (from && to && to < from) {
    errors['toBeforeFrom'] = true;
  }

  const start = group.get('start_time')?.value;
  const end = group.get('end_time')?.value;
  if (start && end && end <= start) {
    errors['endBeforeStart'] = true;
  }

  return Object.keys(errors).length ? errors : null;
};

@Component({
  selector: 'app-absence-modal',
  standalone: true,
  imports: [
    ReactiveFormsModule,
    ModalComponent,
    SelectComponent,
    DatePickerComponent,
    TimePickerComponent,
    ToggleComponent,
    InputComponent,
    TextareaComponent,
  ],
  templateUrl: './absence-modal.component.html',
})
export class AbsenceModalComponent {
  readonly isOpen = input.required<boolean>();
  readonly absence = input<WorkerAbsence | null>(null);
  readonly submitting = input.required<boolean>();

  readonly close = output<void>();
  readonly formSubmit = output<AbsenceFormValue>();
  readonly remove = output<void>();

  private readonly fb = inject(FormBuilder);
  private readonly lookups = inject(LookupService);

  protected readonly isEdit = computed(() => this.absence() !== null);
  protected readonly isFullDay = signal(true);

  protected readonly absenceTypeOptions = computed<SelectOption[]>(() =>
    this.lookups.absenceTypes().map((type) => ({ value: String(type.id), label: type.name })),
  );

  protected readonly form = this.fb.nonNullable.group(
    {
      from: [''],
      to: [''],
      date: [''],
      is_full_day: [true],
      start_time: [''],
      end_time: [''],
      absence_type_id: [''],
      reason: ['', [Validators.maxLength(120)]],
      notes: ['', [Validators.maxLength(5000)]],
    },
    { validators: rangeAndTimeValidator },
  );

  constructor() {
    this.form.controls.is_full_day.valueChanges
      .pipe(takeUntilDestroyed())
      .subscribe((fullDay) => this.applyFullDay(!!fullDay));

    effect(() => {
      if (!this.isOpen()) return;
      const current = this.absence();

      this.form.reset(
        {
          from: '',
          to: '',
          date: current?.date ?? '',
          is_full_day: current?.is_full_day ?? true,
          start_time: current?.start_time ? current.start_time.slice(0, 5) : '',
          end_time: current?.end_time ? current.end_time.slice(0, 5) : '',
          absence_type_id: current?.absence_type_id ? String(current.absence_type_id) : '',
          reason: current?.reason ?? '',
          notes: current?.notes ?? '',
        },
        { emitEvent: false },
      );

      this.applyMode(current === null);
      this.applyFullDay(current?.is_full_day ?? true);
    });
  }

  private applyFullDay(fullDay: boolean): void {
    this.isFullDay.set(fullDay);
    const { start_time, end_time } = this.form.controls;
    if (fullDay) {
      start_time.clearValidators();
      end_time.clearValidators();
      start_time.setValue('', { emitEvent: false });
      end_time.setValue('', { emitEvent: false });
    } else {
      start_time.setValidators([Validators.required]);
      end_time.setValidators([Validators.required]);
    }
    start_time.updateValueAndValidity({ emitEvent: false });
    end_time.updateValueAndValidity({ emitEvent: false });
  }

  private applyMode(isCreate: boolean): void {
    const { from, to, date } = this.form.controls;
    if (isCreate) {
      from.setValidators([Validators.required]);
      to.setValidators([Validators.required]);
      date.clearValidators();
    } else {
      from.clearValidators();
      to.clearValidators();
      date.setValidators([Validators.required]);
    }
    from.updateValueAndValidity({ emitEvent: false });
    to.updateValueAndValidity({ emitEvent: false });
    date.updateValueAndValidity({ emitEvent: false });
  }

  protected onSubmit(): void {
    if (this.submitting()) return;
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    const raw = this.form.getRawValue();
    const fullDay = raw.is_full_day;
    this.formSubmit.emit({
      from: raw.from,
      to: raw.to,
      date: raw.date,
      is_full_day: fullDay,
      start_time: fullDay ? null : raw.start_time || null,
      end_time: fullDay ? null : raw.end_time || null,
      absence_type_id: raw.absence_type_id === '' ? null : Number(raw.absence_type_id),
      reason: raw.reason.trim() === '' ? null : raw.reason.trim(),
      notes: raw.notes.trim() === '' ? null : raw.notes.trim(),
    });
  }

  protected hasFieldError(field: 'from' | 'to' | 'date' | 'start_time' | 'end_time'): boolean {
    return hasFieldError(this.form.controls[field]);
  }

  protected hasValidation(field: 'reason' | 'notes', key: string): boolean {
    return hasValidationError(this.form.controls[field], key);
  }

  protected showToBeforeFrom(): boolean {
    return this.form.controls.to.touched && this.form.hasError('toBeforeFrom');
  }

  protected showEndBeforeStart(): boolean {
    return this.form.controls.end_time.touched && this.form.hasError('endBeforeStart');
  }
}
