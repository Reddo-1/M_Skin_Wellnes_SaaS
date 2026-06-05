import { Component, input } from '@angular/core';

@Component({
  selector: 'app-table-loading-overlay',
  standalone: true,
  templateUrl: './table-loading-overlay.component.html',
})
export class TableLoadingOverlayComponent {
  readonly align = input<'center' | 'top'>('center');
}
