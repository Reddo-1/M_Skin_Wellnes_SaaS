import { Component, input } from '@angular/core';

@Component({
  selector: 'app-table-scroll-hint',
  standalone: true,
  templateUrl: './table-scroll-hint.component.html',
})
export class TableScrollHintComponent {
  readonly message = input<string>('Desliza la tabla para ver todas las columnas');
}
