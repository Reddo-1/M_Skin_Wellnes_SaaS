import { Component, ElementRef, computed, effect, forwardRef, inject, input, output, signal } from '@angular/core';
import { ControlValueAccessor, NG_VALUE_ACCESSOR } from '@angular/forms';

let nextId = 0;

const WEEKDAYS = ['L', 'M', 'X', 'J', 'V', 'S', 'D'];
const MONTHS = [
  'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
  'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre',
];

const pad = (value: number): string => value.toString().padStart(2, '0');

interface CalendarCell {
  day: number;
  iso: string;
  today: boolean;
  selected: boolean;
}

@Component({
  selector: 'app-date-picker',
  standalone: true,
  templateUrl: './date-picker.component.html',
  providers: [
    { provide: NG_VALUE_ACCESSOR, useExisting: forwardRef(() => DatePickerComponent), multi: true },
  ],
})
export class DatePickerComponent implements ControlValueAccessor {
  readonly label = input<string>();
  readonly required = input(false);
  readonly placeholder = input('Selecciona una fecha');
  readonly invalid = input(false);

  readonly valueChange = output<string | null>();

  protected readonly fieldId = `app-date-picker-${nextId++}`;
  protected readonly weekdays = WEEKDAYS;

  private readonly host = inject<ElementRef<HTMLElement>>(ElementRef);

  protected readonly value = signal<string | null>(null);
  protected readonly disabled = signal(false);
  protected readonly open = signal(false);
  protected readonly openUpward = signal(false);
  protected readonly mode = signal<'days' | 'years'>('days');

  protected readonly viewYear = signal(0);
  protected readonly viewMonth = signal(0);
  protected readonly yearBase = signal(0);

  private onChange: (value: string | null) => void = () => {};
  private onTouched: () => void = () => {};

  constructor() {
    this.resetViewToToday();

    effect((onCleanup) => {
      if (!this.open()) return;
      const onOutsideClick = (event: MouseEvent) => {
        if (!this.host.nativeElement.contains(event.target as Node)) {
          this.close();
        }
      };
      const onKeydown = (event: KeyboardEvent) => {
        if (event.key === 'Escape') {
          event.stopPropagation();
          this.close();
        }
      };
      document.addEventListener('mousedown', onOutsideClick);
      document.addEventListener('keydown', onKeydown, true);
      onCleanup(() => {
        document.removeEventListener('mousedown', onOutsideClick);
        document.removeEventListener('keydown', onKeydown, true);
      });
    });
  }

  protected readonly displayValue = computed(() => {
    const current = this.value();
    if (!current) return '';
    const [year, month, day] = current.split('-');
    return `${day}/${month}/${year}`;
  });

  protected readonly monthLabel = computed(() => `${MONTHS[this.viewMonth()]} ${this.viewYear()}`);

  protected readonly yearRangeLabel = computed(() => `${this.yearBase()} – ${this.yearBase() + 11}`);

  protected readonly yearGrid = computed(() =>
    Array.from({ length: 12 }, (_, index) => this.yearBase() + index),
  );

  protected readonly cells = computed<(CalendarCell | null)[]>(() => {
    const year = this.viewYear();
    const month = this.viewMonth();
    const selected = this.value();

    const now = new Date();
    const todayIso = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;

    const firstWeekday = (new Date(year, month, 1).getDay() + 6) % 7;
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    const grid: (CalendarCell | null)[] = [];
    for (let blank = 0; blank < firstWeekday; blank++) grid.push(null);
    for (let day = 1; day <= daysInMonth; day++) {
      const iso = `${year}-${pad(month + 1)}-${pad(day)}`;
      grid.push({ day, iso, today: iso === todayIso, selected: iso === selected });
    }
    return grid;
  });

  writeValue(value: string | null): void {
    const normalized = value ? value.slice(0, 10) : null;
    this.value.set(normalized);
    this.open.set(false);
    this.mode.set('days');
    this.syncViewToValue();
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

  protected toggle(): void {
    if (this.disabled()) return;
    if (!this.open()) {
      this.mode.set('days');
      this.syncViewToValue();
      const rect = this.host.nativeElement.getBoundingClientRect();
      this.openUpward.set(rect.bottom > window.innerHeight * 0.6);
    }
    this.open.update((open) => !open);
  }

  protected close(): void {
    this.open.set(false);
  }

  protected markTouched(): void {
    this.onTouched();
  }

  protected prevMonth(): void {
    if (this.viewMonth() === 0) {
      this.viewMonth.set(11);
      this.viewYear.update((year) => year - 1);
    } else {
      this.viewMonth.update((month) => month - 1);
    }
  }

  protected nextMonth(): void {
    if (this.viewMonth() === 11) {
      this.viewMonth.set(0);
      this.viewYear.update((year) => year + 1);
    } else {
      this.viewMonth.update((month) => month + 1);
    }
  }

  protected openYears(): void {
    this.yearBase.set(this.viewYear() - (this.viewYear() % 12));
    this.mode.set('years');
  }

  protected prevYears(): void {
    this.yearBase.update((base) => base - 12);
  }

  protected nextYears(): void {
    this.yearBase.update((base) => base + 12);
  }

  protected selectYear(year: number): void {
    this.viewYear.set(year);
    this.mode.set('days');
  }

  protected select(iso: string): void {
    this.value.set(iso);
    this.onChange(iso);
    this.valueChange.emit(iso);
    this.onTouched();
    this.open.set(false);
  }

  protected clear(): void {
    this.value.set(null);
    this.onChange(null);
    this.valueChange.emit(null);
    this.onTouched();
    this.open.set(false);
  }

  private syncViewToValue(): void {
    const current = this.value();
    if (!current) {
      this.resetViewToToday();
      return;
    }
    const [year, month] = current.split('-');
    this.viewYear.set(Number(year));
    this.viewMonth.set(Number(month) - 1);
  }

  private resetViewToToday(): void {
    const now = new Date();
    this.viewYear.set(now.getFullYear());
    this.viewMonth.set(now.getMonth());
  }
}
