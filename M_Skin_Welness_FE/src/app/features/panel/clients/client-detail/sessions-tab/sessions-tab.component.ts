import { CurrencyPipe, DatePipe } from '@angular/common';
import { Component, computed, effect, inject, input, signal } from '@angular/core';
import { AppointmentSummary } from '../../../../../core/models/appointment.model';
import { User } from '../../../../../core/models/user.model';
import { AppointmentService } from '../../../../../core/services/appointment.service';
import { NotificationService } from '../../../../../core/services/notification.service';
import { loadResourceError } from '../../../../../core/utils/form.util';
import { NormalizePipe } from '../../../../../shared/pipes/normalize.pipe';
import { AlertComponent } from '../../../../../shared/ui/alert/alert.component';

@Component({
  selector: 'app-sessions-tab',
  standalone: true,
  imports: [CurrencyPipe, DatePipe, NormalizePipe, AlertComponent],
  templateUrl: './sessions-tab.component.html',
})
export class SessionsTabComponent {
  readonly client = input.required<User>();

  private readonly appointments = inject(AppointmentService);
  private readonly notifications = inject(NotificationService);

  protected readonly items = signal<AppointmentSummary[]>([]);
  protected readonly loading = signal(false);
  protected readonly errorMessage = signal<string | null>(null);

  protected readonly visible = computed(() =>
    this.items()
      .filter((appointment) => appointment.status?.name === 'realizada')
      .sort((a, b) => this.startsAtMillis(b) - this.startsAtMillis(a)),
  );

  constructor() {
    effect(() => {
      const userId = this.client().id;
      void this.load(userId);
    });
  }

  private startsAtMillis(appointment: AppointmentSummary): number {
    return appointment.starts_at ? new Date(appointment.starts_at).getTime() : 0;
  }

  private async load(userId: number): Promise<void> {
    this.loading.set(true);
    this.errorMessage.set(null);
    try {
      const items = await this.appointments.listByClient(userId);
      this.items.set(items);
    } catch {
      const message = loadResourceError('las sesiones');
      this.errorMessage.set(message);
    } finally {
      this.loading.set(false);
    }
  }
}
