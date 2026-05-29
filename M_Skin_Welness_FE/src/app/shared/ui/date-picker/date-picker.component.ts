import {
  AfterViewInit,
  Component,
  ElementRef,
  OnDestroy,
  forwardRef,
  input,
  viewChild,
} from '@angular/core';
import { ControlValueAccessor, NG_VALUE_ACCESSOR } from '@angular/forms';
import flatpickr from 'flatpickr';
import { Spanish } from 'flatpickr/dist/l10n/es.js';

let nextId = 0;

@Component({
  selector: 'app-date-picker',
  standalone: true,
  templateUrl: './date-picker.component.html',
  providers: [
    { provide: NG_VALUE_ACCESSOR, useExisting: forwardRef(() => DatePickerComponent), multi: true },
  ],
})
export class DatePickerComponent implements AfterViewInit, OnDestroy, ControlValueAccessor {
  readonly label = input<string>();
  readonly required = input(false);
  readonly placeholder = input('Selecciona una fecha');
  readonly invalid = input(false);
  readonly enableTime = input(false);
  readonly timeOnly = input(false);
  readonly minDate = input<string>();
  readonly maxDate = input<string>();

  readonly inputRef = viewChild.required<ElementRef<HTMLInputElement>>('dateInput');

  protected readonly inputId = `app-date-picker-${nextId++}`;

  private instance: flatpickr.Instance | undefined;
  private currentValue: string | null = null;
  private disabledState = false;
  private onChange: (value: string | null) => void = () => {};
  private onTouched: () => void = () => {};

  ngAfterViewInit(): void {
    const timeOnly = this.timeOnly();
    const withTime = this.enableTime() || timeOnly;
    this.instance = flatpickr(this.inputRef().nativeElement, {
      locale: Spanish,
      enableTime: withTime,
      noCalendar: timeOnly,
      time_24hr: true,
      dateFormat: timeOnly ? 'H:i' : withTime ? 'Y-m-d H:i' : 'Y-m-d',
      minDate: this.minDate(),
      maxDate: this.maxDate(),
      defaultDate: this.currentValue ?? undefined,
      clickOpens: !this.disabledState,
      onChange: (_dates, dateStr) => {
        this.currentValue = dateStr || null;
        this.onChange(this.currentValue);
      },
      onClose: () => this.onTouched(),
    });
    this.inputRef().nativeElement.disabled = this.disabledState;
  }

  ngOnDestroy(): void {
    this.instance?.destroy();
  }

  writeValue(value: string | null): void {
    this.currentValue = value ?? null;
    if (!this.instance) return;
    if (value) {
      this.instance.setDate(value, false);
    } else {
      this.instance.clear();
    }
  }

  registerOnChange(fn: (value: string | null) => void): void {
    this.onChange = fn;
  }

  registerOnTouched(fn: () => void): void {
    this.onTouched = fn;
  }

  setDisabledState(isDisabled: boolean): void {
    this.disabledState = isDisabled;
    this.instance?.set('clickOpens', !isDisabled);
    this.inputRef().nativeElement.disabled = isDisabled;
  }
}
