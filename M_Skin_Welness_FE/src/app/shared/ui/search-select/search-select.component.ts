import {
  Component,
  ElementRef,
  effect,
  inject,
  input,
  output,
  signal,
  viewChild,
} from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { Subject, debounceTime } from 'rxjs';

export interface SearchSelectOption {
  value: string;
  label: string;
  sublabel?: string;
}

@Component({
  selector: 'app-search-select',
  standalone: true,
  templateUrl: './search-select.component.html',
})
export class SearchSelectComponent {
  readonly options = input.required<SearchSelectOption[]>();
  readonly selected = input<string>('');
  readonly selectedLabel = input<string | null>(null);
  readonly placeholder = input<string>('Selecciona una opción');
  readonly searchPlaceholder = input<string>('Escribe para buscar…');
  readonly loading = input<boolean>(false);
  readonly disabled = input<boolean>(false);
  readonly invalid = input<boolean>(false);

  readonly searchInput = output<string>();
  readonly selectionChange = output<SearchSelectOption>();

  private readonly host = inject(ElementRef<HTMLElement>);
  private readonly searchBox = viewChild<ElementRef<HTMLInputElement>>('searchBox');

  protected readonly isOpen = signal(false);
  protected readonly query = signal('');

  //undefined hasta el primer tecleo: el effect ignora ese valor inicial y no lanza búsqueda
  private readonly queryChanges = new Subject<string>();
  private readonly debouncedQuery = toSignal(this.queryChanges.pipe(debounceTime(250)));

  constructor() {
    effect(() => {
      const value = this.debouncedQuery();
      if (value !== undefined) this.searchInput.emit(value);
    });

    effect((onCleanup) => {
      if (!this.isOpen()) return;
      this.searchBox()?.nativeElement.focus();
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
    const next = !this.isOpen();
    this.isOpen.set(next);
    //al abrir pide la lista aunque no se haya tecleado, para tener resultados de entrada
    if (next) this.searchInput.emit(this.query());
  }

  protected onQueryInput(value: string): void {
    this.query.set(value);
    this.queryChanges.next(value);
  }

  protected pick(option: SearchSelectOption, event: MouseEvent): void {
    event.stopPropagation();
    this.isOpen.set(false);
    this.selectionChange.emit(option);
  }

  protected isSelected(value: string): boolean {
    return value === this.selected();
  }
}
