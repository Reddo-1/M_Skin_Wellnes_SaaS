import { Component, computed, forwardRef, input, signal } from '@angular/core';
import { ControlValueAccessor, NG_VALUE_ACCESSOR } from '@angular/forms';

export type InputType = 'text' | 'number' | 'email' | 'password' | 'tel';

let nextId = 0;

@Component({
  selector: 'app-input',
  standalone: true,
  templateUrl: './input.component.html',
  providers: [
    { provide: NG_VALUE_ACCESSOR, useExisting: forwardRef(() => InputComponent), multi: true },
  ],
})
export class InputComponent implements ControlValueAccessor {
  readonly label = input<string>();
  readonly required = input(false);
  readonly type = input<InputType>('text');
  readonly placeholder = input('');
  readonly invalid = input(false);
  readonly hint = input<string>();
  readonly autocomplete = input<string>();
  readonly min = input<number | string>();
  readonly max = input<number | string>();
  readonly step = input<number | string>();
  readonly revealToggle = input(false);

  protected readonly inputId = `app-input-${nextId++}`;
  protected readonly value = signal<string | number | null>('');
  protected readonly disabled = signal(false);
  protected readonly revealed = signal(false);

  protected readonly effectiveType = computed(() =>
    this.type() === 'password' && this.revealToggle() && this.revealed() ? 'text' : this.type(),
  );

  private onChange: (value: string | number | null) => void = () => {};
  private onTouched: () => void = () => {};

  writeValue(value: string | number | null): void {
    this.value.set(value ?? '');
  }

  registerOnChange(fn: (value: string | number | null) => void): void {
    this.onChange = fn;
  }

  registerOnTouched(fn: () => void): void {
    this.onTouched = fn;
  }

  setDisabledState(isDisabled: boolean): void {
    this.disabled.set(isDisabled);
  }

  protected handleInput(event: Event): void {
    const raw = (event.target as HTMLInputElement).value;
    const value = this.type() === 'number' ? (raw === '' ? null : Number(raw)) : raw;
    this.value.set(value);
    this.onChange(value);
  }

  protected handleBlur(): void {
    this.onTouched();
  }

  protected toggleReveal(): void {
    this.revealed.update((current) => !current);
  }
}
