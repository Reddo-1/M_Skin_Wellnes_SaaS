import { Component, input } from '@angular/core';

@Component({
  selector: 'app-placeholder',
  standalone: true,
  templateUrl: './placeholder.component.html',
})
export class PlaceholderComponent {
  readonly title = input.required<string>();
}
