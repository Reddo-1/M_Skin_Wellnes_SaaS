import { Component, ElementRef, computed, effect, inject, input, output, signal } from '@angular/core';

export interface MultiSelectOption {
  id: number;
  name: string;
}

@Component({
  selector: 'app-multi-select',
  standalone: true,
  templateUrl: './multi-select.component.html',
})
export class MultiSelectComponent {
  readonly options = input.required<MultiSelectOption[]>();
  readonly selectedIds = input<number[]>([]);
  readonly placeholder = input<string>('Selecciona una opción');
  readonly disabled = input<boolean>(false);

  readonly selectionChange = output<number[]>();

  private readonly host = inject(ElementRef<HTMLElement>);

  protected readonly isOpen = signal(false);

  protected readonly selectedOptions = computed(() => {
    const ids = new Set(this.selectedIds());
    return this.options().filter((option) => ids.has(option.id));
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

  protected toggleOption(optionId: number, event: MouseEvent): void {
    event.stopPropagation();
    const current = this.selectedIds();
    const next = current.includes(optionId)
      ? current.filter((id) => id !== optionId)
      : [...current, optionId];
    this.selectionChange.emit(next);
  }

  protected removeOption(optionId: number, event: MouseEvent): void {
    event.stopPropagation();
    this.selectionChange.emit(this.selectedIds().filter((id) => id !== optionId));
  }

  protected isSelected(optionId: number): boolean {
    return this.selectedIds().includes(optionId);
  }
}
