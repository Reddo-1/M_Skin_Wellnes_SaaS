import { CurrencyPipe, DatePipe } from '@angular/common';
import { Component, computed, inject, signal } from '@angular/core';
import { AuthService } from '../../../core/services/auth.service';
import { DashboardService } from '../../../core/services/dashboard.service';
import { NotificationService } from '../../../core/services/notification.service';
import { loadResourceError } from '../../../core/utils/form.util';
import { AlertComponent } from '../../../shared/ui/alert/alert.component';
import { StatCardComponent, StatCardTrend } from '../../../shared/ui/stat-card/stat-card.component';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [CurrencyPipe, DatePipe, AlertComponent, StatCardComponent],
  templateUrl: './dashboard.component.html',
})
export class DashboardComponent {
  protected readonly auth = inject(AuthService);
  protected readonly dashboard = inject(DashboardService);
  private readonly notifications = inject(NotificationService);

  protected readonly loading = signal(true);
  protected readonly errorMessage = signal<string | null>(null);

  protected readonly revenueDeltaText = computed(() => {
    const summary = this.dashboard.summary();
    const current = summary?.revenue.this_month ?? 0;
    const previous = summary?.revenue.last_month ?? 0;
    if (previous === 0) {
      return current > 0 ? 'Sin ingresos el mes pasado' : 'Sin actividad de ventas todavía';
    }
    const delta = ((current - previous) / previous) * 100;
    const sign = delta >= 0 ? '+' : '';
    return `${sign}${delta.toFixed(1)}% respecto al mes anterior`;
  });

  protected readonly revenueDeltaTrend = computed<StatCardTrend>(() => {
    const summary = this.dashboard.summary();
    const current = summary?.revenue.this_month ?? 0;
    const previous = summary?.revenue.last_month ?? 0;
    if (previous === 0) return 'neutral';
    if (current > previous) return 'up';
    if (current < previous) return 'down';
    return 'neutral';
  });

  constructor() {
    void this.load();
  }

  protected async load(): Promise<void> {
    this.loading.set(true);
    this.errorMessage.set(null);
    try {
      await this.dashboard.fetchSummary();
    } catch {
      const message = loadResourceError('los datos del panel');
      this.errorMessage.set(message);
      this.notifications.toast.error(message);
    } finally {
      this.loading.set(false);
    }
  }
}
