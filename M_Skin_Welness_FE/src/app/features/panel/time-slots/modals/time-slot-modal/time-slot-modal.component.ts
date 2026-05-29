import { Component, computed, effect, inject, input, output } from '@angular/core';
import { AbstractControl, FormBuilder, ReactiveFormsModule, ValidationErrors, Validators } from '@angular/forms';
import { TimeSlot } from '../../../../../core/models/time-slot.model';
import { hasFieldError, hasValidationError } from '../../../../../core/utils/form.util';
import { ModalComponent } from '../../../../../shared/ui/modal/modal.component';
import { InputComponent } from '../../../../../shared/ui/input/input.component';
import { DatePickerComponent } from '../../../../../shared/ui/date-picker/date-picker.component';
import { ToggleComponent } from '../../../../../shared/ui/toggle/toggle.component';

export interface TimeSlotFormValue {
  name: string;
  start_time: string;
  end_time: string;
  is_active: boolean;
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
  selector: 'app-time-slot-modal',
  standalone: true,
  imports: [ReactiveFormsModule, ModalComponent, InputComponent, DatePickerComponent, ToggleComponent],
  templateUrl: './time-slot-modal.component.html',
})
export class TimeSlotModalComponent {
  readonly isOpen = input.required<boolean>();
  readonly timeSlot = input<TimeSlot | null>(null);
  readonly submitting = input.required<boolean>();

  readonly close = output<void>();
  readonly formSubmit = output<TimeSlotFormValue>();

  private readonly fb = inject(FormBuilder);

  protected readonly isEdit = computed(() => this.timeSlot() !== null);

  protected readonly form = this.fb.nonNullable.group(
    {
      name: ['', [Validators.maxLength(50)]],
      start_time: ['', [Validators.required]],
      end_time: ['', [Validators.required]],
      is_active: [true],
    },
    { validators: endAfterStartValidator },
  );

  constructor() {
    effect(() => {
      if (!this.isOpen()) return;
      const current = this.timeSlot();
      this.form.reset({
        name: current?.name ?? '',
        start_time: current ? current.start_time.slice(0, 5) : '',
        end_time: current ? current.end_time.slice(0, 5) : '',
        is_active: current?.is_active ?? true,
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

  protected hasFieldError(field: 'name' | 'start_time' | 'end_time'): boolean {
    return hasFieldError(this.form.controls[field]);
  }

  protected hasValidationError(field: 'name' | 'start_time' | 'end_time', key: string): boolean {
    return hasValidationError(this.form.controls[field], key);
  }

  protected showEndBeforeStart(): boolean {
    return this.form.controls.end_time.touched && this.form.hasError('endBeforeStart');
  }
}
