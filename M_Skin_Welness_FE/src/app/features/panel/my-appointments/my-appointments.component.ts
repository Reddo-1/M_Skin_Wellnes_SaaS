import { Component, computed, inject, signal } from '@angular/core';
import { AppointmentSummary } from '../../../core/models/appointment.model';
import { AppointmentService } from '../../../core/services/appointment.service';
import { loadResourceError } from '../../../core/utils/form.util';
import { AlertComponent } from '../../../shared/ui/alert/alert.component';
import { IconComponent } from '../../../shared/ui/icon/icon.component';
import {
  SegmentedControlComponent,
  SegmentedControlOption,
} from '../../../shared/ui/segmented-control/segmented-control.component';
import { AppointmentCardComponent } from './appointment-card/appointment-card.component';

type AppointmentFilter = 'upcoming' | 'past' | 'all';

//estados no terminales = "próximas"; el resto (realizada/cancelada/no_presentada) = "historial"
const UPCOMING_STATUSES = ['confirmada', 'en_curso'];

const FILTER_OPTIONS: SegmentedControlOption<AppointmentFilter>[] = [
  { value: 'upcoming', label: 'Próximas' },
  { value: 'past', label: 'Historial' },
  { value: 'all', label: 'Todas' },
];

@Component({
  selector: 'app-my-appointments',
  standalone: true,
  imports: [AlertComponent, IconComponent, SegmentedControlComponent, AppointmentCardComponent],
  templateUrl: './my-appointments.component.html',
})
export class MyAppointmentsComponent {
  private readonly appointments = inject(AppointmentService);

  protected readonly items = signal<AppointmentSummary[]>([]);
  protected readonly loading = signal(false);
  protected readonly errorMessage = signal<string | null>(null);
  protected readonly filter = signal<AppointmentFilter>('upcoming');

  protected readonly filterOptions = FILTER_OPTIONS;

  private readonly upcoming = computed(() =>
    this.items()
      .filter((appointment) => UPCOMING_STATUSES.includes(appointment.status?.name ?? ''))
      .sort((a, b) => this.startTimestamp(a) - this.startTimestamp(b)),
  );

  private readonly past = computed(() =>
    this.items()
      .filter((appointment) => !UPCOMING_STATUSES.includes(appointment.status?.name ?? ''))
      .sort((a, b) => this.startTimestamp(b) - this.startTimestamp(a)),
  );

  protected readonly visible = computed(() => {
    switch (this.filter()) {
      case 'upcoming':
        return this.upcoming();
      case 'past':
        return this.past();
      default:
        return [...this.upcoming(), ...this.past()];
    }
  });

  protected readonly emptyMessage = computed(() =>
    this.filter() === 'past' ? 'No tienes citas anteriores.' : 'No tienes citas próximas.',
  );

  constructor() {
    void this.load();
  }

  protected onFilterChange(value: AppointmentFilter): void {
    this.filter.set(value);
  }

  private startTimestamp(appointment: AppointmentSummary): number {
    return appointment.starts_at ? new Date(appointment.starts_at).getTime() : 0;
  }

  private async load(): Promise<void> {
    this.loading.set(true);
    this.errorMessage.set(null);
    try {
      this.items.set(await this.appointments.listMine());
    } catch {
      this.errorMessage.set(loadResourceError('tus citas'));
    } finally {
      this.loading.set(false);
    }
  }
}
