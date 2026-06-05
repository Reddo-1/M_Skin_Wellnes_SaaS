import { Component, effect, input, output, signal } from '@angular/core';
import { toObservable, toSignal } from '@angular/core/rxjs-interop';
import { debounceTime, skip } from 'rxjs';

@Component({
  selector: 'app-search-input',
  standalone: true,
  templateUrl: './search-input.component.html',
})
export class SearchInputComponent {
  readonly placeholder = input<string>('Buscar…');

  readonly search = output<string>();

  protected readonly query = signal('');

  //skip(1) descarta el valor inicial vacío para no emitir una búsqueda al arrancar
  private readonly debouncedQuery = toSignal(
    toObservable(this.query).pipe(skip(1), debounceTime(300)),
  );

  constructor() {
    effect(() => {
      const value = this.debouncedQuery();
      if (value !== undefined) this.search.emit(value);
    });
  }

  protected onInput(value: string): void {
    this.query.set(value);
  }
}
