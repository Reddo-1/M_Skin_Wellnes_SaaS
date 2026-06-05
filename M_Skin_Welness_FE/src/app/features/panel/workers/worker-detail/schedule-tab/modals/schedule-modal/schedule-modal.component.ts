import { Component, computed, effect, inject, input, output, signal } from '@angular/core';
import { AbstractControl, FormBuilder, ReactiveFormsModule, ValidationErrors, Validators } from '@angular/forms';
import { TimeSlotService } from '../../../../../../../core/services/time-slot.service';
import { WorkerSchedule } from '../../../../../../../core/models/worker-schedule.model';
import { hasFieldError } from '../../../../../../../core/utils/form.util';
import { ModalComponent } from '../../../../../../../shared/ui/modal/modal.component';
import { SelectComponent, SelectOption } from '../../../../../../../shared/ui/select/select.component';
import { DatePickerComponent } from '../../../../../../../shared/ui/date-picker/date-picker.component';

export interface ScheduleFormValue {
  weekday: number;
  time_slot_id: number;
  start_date: string;
  end_date: string | null;
}

const endAfterStartValidator = (group: AbstractControl): ValidationErrors | null => {
  const start = group.get('start_date')?.value;
  const end = group.get('end_date')?.value;
  if (start && end && end < start) {
    return { endBeforeStart: true };
  }
  return null;
};

@Component({
  selector: 'app-schedule-modal',
  standalone: true,
  imports: [ReactiveFormsModule, ModalComponent, SelectComponent, DatePickerComponent],
  templateUrl: './schedule-modal.component.html',
})
export class ScheduleModalComponent {
  readonly isOpen = input.required<boolean>();
  readonly schedule = input<WorkerSchedule | null>(null);
  readonly submitting = input.required<boolean>();

  readonly close = output<void>();
  readonly formSubmit = output<ScheduleFormValue>();
  readonly remove = output<void>();

  private readonly fb = inject(FormBuilder);
  private readonly timeSlots = inject(TimeSlotService);

  protected readonly isEdit = computed(() => this.schedule() !== null);

  protected readonly weekdayOptions: SelectOption[] = [
    { value: '1', label: 'Lunes' },
    { value: '2', label: 'Martes' },
    { value: '3', label: 'Miércoles' },
    { value: '4', label: 'Jueves' },
    { value: '5', label: 'Viernes' },
    { value: '6', label: 'Sábado' },
    { value: '7', label: 'Domingo' },
  ];

  protected readonly timeSlotOptions = signal<SelectOption[]>([]);

  protected readonly form = this.fb.nonNullable.group(
    {
      weekday: ['', [Validators.required]],
      time_slot_id: ['', [Validators.required]],
      start_date: ['', [Validators.required]],
      end_date: [''],
    },
    { validators: endAfterStartValidator },
  );

  constructor() {
    void this.loadTimeSlots();

    effect(() => {
      if (!this.isOpen()) return;
      const current = this.schedule();
      this.form.reset({
        weekday: current ? String(current.weekday) : '',
        time_slot_id: current ? String(current.time_slot_id) : '',
        start_date: current?.start_date ?? '',
        end_date: current?.end_date ?? '',
      });
    });
  }

  private async loadTimeSlots(): Promise<void> {
    try {
      const page = await this.timeSlots.list({ is_active: true });
      this.timeSlotOptions.set(
        page.data.map((slot) => ({
          value: String(slot.id),
          label: this.timeSlotLabel(slot.name, slot.start_time, slot.end_time),
        })),
      );
    } catch {
      this.timeSlotOptions.set([]);
    }
  }

  private timeSlotLabel(name: string | null, start: string, end: string): string {
    const range = `${start.slice(0, 5)}–${end.slice(0, 5)}`;
    return name ? `${name} (${range})` : range;
  }

  protected onSubmit(): void {
    if (this.submitting()) return;
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    const raw = this.form.getRawValue();
    this.formSubmit.emit({
      weekday: Number(raw.weekday),
      time_slot_id: Number(raw.time_slot_id),
      start_date: raw.start_date,
      end_date: raw.end_date === '' ? null : raw.end_date,
    });
  }

  protected hasFieldError(field: 'weekday' | 'time_slot_id' | 'start_date'): boolean {
    return hasFieldError(this.form.controls[field]);
  }

  protected showEndBeforeStart(): boolean {
    return this.form.controls.end_date.touched && this.form.hasError('endBeforeStart');
  }
}
