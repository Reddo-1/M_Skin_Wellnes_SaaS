import { Component, computed, input } from '@angular/core';

export type AlertVariant = 'success' | 'error' | 'warning' | 'info';

const VARIANT_CLASSES: Record<AlertVariant, { container: string; icon: string }> = {
  success: { container: 'border-success-500 bg-success-50', icon: 'text-success-500' },
  error: { container: 'border-error-500 bg-error-50', icon: 'text-error-500' },
  warning: { container: 'border-warning-500 bg-warning-50', icon: 'text-warning-500' },
  info: { container: 'border-brand-500 bg-brand-50', icon: 'text-brand-500' },
};

@Component({
  selector: 'app-alert',
  standalone: true,
  templateUrl: './alert.component.html',
})
export class AlertComponent {
  readonly variant = input<AlertVariant>('info');
  readonly title = input<string>('');
  readonly message = input.required<string>();

  protected readonly classes = computed(() => VARIANT_CLASSES[this.variant()]);
}
