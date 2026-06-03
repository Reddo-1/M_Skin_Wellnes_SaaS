import { CurrencyPipe, DatePipe } from '@angular/common';
import { Component, computed, input } from '@angular/core';
import { AppointmentSummary } from '../../../../core/models/appointment.model';
import { NormalizePipe } from '../../../../shared/pipes/normalize.pipe';
import { IconComponent } from '../../../../shared/ui/icon/icon.component';

interface StatusStyle {
  badge: string;
  rail: string;
}

const STATUS_STYLE: Record<string, StatusStyle> = {
  confirmada: { badge: 'bg-success-50 text-success-600', rail: 'bg-success-500' },
  en_curso: { badge: 'bg-brand-50 text-brand-600', rail: 'bg-brand-500' },
  realizada: { badge: 'bg-surface-alt text-ink', rail: 'bg-success-300' },
  cancelada: { badge: 'bg-surface-alt text-muted', rail: 'bg-gray-300' },
  no_presentada: { badge: 'bg-error-50 text-error-600', rail: 'bg-error-500' },
};

const FALLBACK_STYLE: StatusStyle = { badge: 'bg-surface-alt text-muted', rail: 'bg-gray-300' };

@Component({
  selector: 'app-appointment-card',
  standalone: true,
  imports: [DatePipe, CurrencyPipe, NormalizePipe, IconComponent],
  templateUrl: './appointment-card.component.html',
})
export class AppointmentCardComponent {
  readonly appointment = input.required<AppointmentSummary>();

  protected readonly style = computed(
    () => STATUS_STYLE[this.appointment().status?.name ?? ''] ?? FALLBACK_STYLE,
  );

  protected readonly durationMinutes = computed(() => {
    const appointment = this.appointment();
    return appointment.actual_duration_minutes ?? appointment.treatment?.duration_minutes ?? null;
  });

  protected readonly price = computed(() => {
    const appointment = this.appointment();
    const raw = appointment.reserved_price ?? appointment.treatment?.price ?? null;
    return raw === null ? null : Number(raw);
  });
}
