import { Component, input } from '@angular/core';

@Component({
  selector: 'app-loading-overlay',
  standalone: true,
  templateUrl: './loading-overlay.component.html',
})
export class LoadingOverlayComponent {
  readonly message = input.required<string>();
  readonly description = input<string>('Un momento.');
}
