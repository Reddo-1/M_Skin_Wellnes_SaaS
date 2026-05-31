import { Component, computed, effect, inject, input, output } from '@angular/core';
import { AbstractControl, FormBuilder, ReactiveFormsModule, ValidationErrors, Validators } from '@angular/forms';
import { TimeSlot } from '../../../../../core/models/time-slot.model';
import { hasFieldError, hasValidationError } from '../../../../../core/utils/form.util';
import { ModalComponent } from '../../../../../shared/ui/modal/modal.component';
import { InputComponent } from '../../../../../shared/ui/input/input.component';
import { TimePickerComponent } from '../../../../../shared/ui/time-picker/time-picker.component';
import { ToggleComponent } from '../../../../../shared/ui/toggle/toggle.component';

export interface TimeSlotFormValue {
  name: string;
  start_time: string;
  end_time: string;
  break_start: string | null;
  break_end: string | null;
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

//el descanso es opcional: ambos o ninguno, dentro de la franja y bien ordenado
const breakValidator = (group: AbstractControl): ValidationErrors | null => {
  const start = group.get('start_time')?.value;
  const end = group.get('end_time')?.value;
  const breakStart = group.get('break_start')?.value;
  const breakEnd = group.get('break_end')?.value;
  if (!breakStart && !breakEnd) return null;
  if (!breakStart || !breakEnd) return { breakIncomplete: true };
  if (breakEnd <= breakStart) return { breakOrder: true };
  if ((start && breakStart < start) || (end && breakEnd > end)) return { breakOutside: true };
  return null;
};

@Component({
  selector: 'app-time-slot-modal',
  standalone: true,
  imports: [ReactiveFormsModule, ModalComponent, InputComponent, TimePickerComponent, ToggleComponent],
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
      break_start: [''],
      break_end: [''],
      is_active: [true],
    },
    { validators: [endAfterStartValidator, breakValidator] },
  );

  constructor() {
    effect(() => {
      if (!this.isOpen()) return;
      const current = this.timeSlot();
      this.form.reset({
        name: current?.name ?? '',
        start_time: current ? current.start_time.slice(0, 5) : '',
        end_time: current ? current.end_time.slice(0, 5) : '',
        break_start: current?.break_start ? current.break_start.slice(0, 5) : '',
        break_end: current?.break_end ? current.break_end.slice(0, 5) : '',
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
    const raw = this.form.getRawValue();
    this.formSubmit.emit({
      name: raw.name,
      start_time: raw.start_time,
      end_time: raw.end_time,
      break_start: raw.break_start === '' ? null : raw.break_start,
      break_end: raw.break_end === '' ? null : raw.break_end,
      is_active: raw.is_active,
    });
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

  protected breakError(): string | null {
    if (!this.form.controls.break_start.touched && !this.form.controls.break_end.touched) {
      return null;
    }
    if (this.form.hasError('breakIncomplete')) {
      return 'Indica inicio y fin del descanso, o deja ambos vacíos.';
    }
    if (this.form.hasError('breakOrder')) {
      return 'El fin del descanso debe ser posterior a su inicio.';
    }
    if (this.form.hasError('breakOutside')) {
      return 'El descanso debe quedar dentro de la franja.';
    }
    return null;
  }
}
