import { Component, computed, input } from '@angular/core';
import { RouterLink } from '@angular/router';

export type AlertVariant = 'success' | 'error' | 'warning' | 'info';

interface VariantClasses {
  variant: AlertVariant;
  container: string;
  icon: string;
}

const VARIANT_CLASSES: VariantClasses[] = [
  { variant: 'success', container: 'border-success-500 bg-success-50', icon: 'text-success-500' },
  { variant: 'error', container: 'border-error-500 bg-error-50', icon: 'text-error-500' },
  { variant: 'warning', container: 'border-warning-500 bg-warning-50', icon: 'text-warning-500' },
  { variant: 'info', container: 'border-brand-500 bg-brand-50', icon: 'text-brand-500' },
];

@Component({
  selector: 'app-alert',
  standalone: true,
  imports: [RouterLink],
  templateUrl: './alert.component.html',
})
export class AlertComponent {
  readonly variant = input<AlertVariant>('info');
  readonly title = input<string>('');
  readonly message = input.required<string>();
  readonly linkHref = input<string | null>(null);
  readonly linkText = input<string>('Más información');

  protected readonly classes = computed(
    () => VARIANT_CLASSES.find((entry) => entry.variant === this.variant())!,
  );
}
