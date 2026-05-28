import { Component, ElementRef, computed, effect, inject, input, output, signal } from '@angular/core';

export interface SelectOption {
  value: string;
  label: string;
}

@Component({
  selector: 'app-select',
  standalone: true,
  templateUrl: './select.component.html',
})
export class SelectComponent {
  readonly options = input.required<SelectOption[]>();
  readonly selected = input<string>('');
  readonly placeholder = input<string>('Selecciona una opción');
  readonly disabled = input<boolean>(false);
  readonly invalid = input<boolean>(false);

  readonly selectionChange = output<string>();

  private readonly host = inject(ElementRef<HTMLElement>);

  protected readonly isOpen = signal(false);

  protected readonly selectedLabel = computed(() => {
    const current = this.selected();
    return this.options().find((option) => option.value === current)?.label ?? null;
  });

  constructor() {
    effect((onCleanup) => {
      if (!this.isOpen()) return;
      const closeOnOutsideClick = (event: MouseEvent) => {
        if (!this.host.nativeElement.contains(event.target as Node)) {
          this.isOpen.set(false);
        }
      };
      document.addEventListener('click', closeOnOutsideClick);
      onCleanup(() => document.removeEventListener('click', closeOnOutsideClick));
    });
  }

  protected toggleDropdown(): void {
    if (this.disabled()) return;
    this.isOpen.update((open) => !open);
  }

  protected selectOption(value: string, event: MouseEvent): void {
    event.stopPropagation();
    this.isOpen.set(false);
    if (value !== this.selected()) {
      this.selectionChange.emit(value);
    }
  }

  protected isSelected(value: string): boolean {
    return value === this.selected();
  }
}
