import { Component, computed, forwardRef, input, signal } from '@angular/core';
import { ControlValueAccessor, NG_VALUE_ACCESSOR } from '@angular/forms';
import { SelectComponent, SelectOption } from '../select/select.component';

let nextId = 0;

const pad = (value: number): string => value.toString().padStart(2, '0');

@Component({
  selector: 'app-time-picker',
  standalone: true,
  imports: [SelectComponent],
  templateUrl: './time-picker.component.html',
  providers: [
    { provide: NG_VALUE_ACCESSOR, useExisting: forwardRef(() => TimePickerComponent), multi: true },
  ],
})
export class TimePickerComponent implements ControlValueAccessor {
  readonly label = input<string>();
  readonly required = input(false);
  readonly invalid = input(false);
  readonly minuteStep = input(5);

  protected readonly fieldId = `app-time-picker-${nextId++}`;
  protected readonly hour = signal<string>('');
  protected readonly minute = signal<string>('');
  protected readonly disabled = signal(false);

  protected readonly hourOptions: SelectOption[] = Array.from({ length: 24 }, (_, h) => ({
    value: pad(h),
    label: pad(h),
  }));

  protected readonly minuteOptions = computed<SelectOption[]>(() => {
    const step = this.minuteStep();
    const options: SelectOption[] = [];
    for (let m = 0; m < 60; m += step) {
      options.push({ value: pad(m), label: pad(m) });
    }
    return options;
  });

  private onChange: (value: string | null) => void = () => {};
  private onTouched: () => void = () => {};

  writeValue(value: string | null): void {
    if (value) {
      const [h, m] = value.split(':');
      this.hour.set(h ?? '');
      this.minute.set(m ?? '');
    } else {
      this.hour.set('');
      this.minute.set('');
    }
  }

  registerOnChange(fn: (value: string | null) => void): void {
    this.onChange = fn;
  }

  registerOnTouched(fn: () => void): void {
    this.onTouched = fn;
  }

  setDisabledState(isDisabled: boolean): void {
    this.disabled.set(isDisabled);
  }

  protected setHour(value: string): void {
    this.hour.set(value);
    this.emit();
  }

  protected setMinute(value: string): void {
    this.minute.set(value);
    this.emit();
  }

  private emit(): void {
    const hour = this.hour();
    const minute = this.minute();
    this.onChange(hour !== '' && minute !== '' ? `${hour}:${minute}` : null);
    this.onTouched();
  }
}
