import { Component, input } from '@angular/core';
import { NavIcon } from '../../../core/models/nav-item.model';

@Component({
  selector: 'app-icon',
  standalone: true,
  templateUrl: './icon.component.html',
})
export class IconComponent {
  readonly name = input.required<NavIcon>();
}
