import { Component, input } from '@angular/core';
import { NavIcon } from '../../../core/models/nav-item.model';
import { IconComponent } from '../icon/icon.component';

export type StatCardTrend = 'up' | 'down' | 'neutral';

@Component({
  selector: 'app-stat-card',
  standalone: true,
  imports: [IconComponent],
  templateUrl: './stat-card.component.html',
})
export class StatCardComponent {
  readonly title = input.required<string>();
  readonly value = input.required<string>();
  readonly icon = input.required<NavIcon>();
  readonly subtitle = input<string | null>(null);
  readonly trend = input<StatCardTrend>('neutral');
}
