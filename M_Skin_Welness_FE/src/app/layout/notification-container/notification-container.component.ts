import { Component, inject } from '@angular/core';
import { NotificationService } from '../../core/services/notification.service';
import { AlertComponent, AlertVariant } from '../../shared/ui/alert/alert.component';
import { ModalComponent } from '../../shared/ui/modal/modal.component';

interface VariantStyle {
  variant: AlertVariant;
  ring: string;
  icon: string;
  button: string;
}

const VARIANT_STYLES: VariantStyle[] = [
  { variant: 'success', ring: 'fill-success-50', icon: 'fill-success-600', button: 'bg-success-500 hover:bg-success-600' },
  { variant: 'error', ring: 'fill-error-50', icon: 'fill-error-600', button: 'bg-error-500 hover:bg-error-600' },
  { variant: 'warning', ring: 'fill-warning-50', icon: 'fill-warning-500', button: 'bg-warning-500 hover:bg-warning-700' },
  { variant: 'info', ring: 'fill-brand-50', icon: 'fill-brand-500', button: 'bg-brand-500 hover:bg-brand-600' },
];

@Component({
  selector: 'app-notification-container',
  standalone: true,
  imports: [AlertComponent, ModalComponent],
  templateUrl: './notification-container.component.html',
})
export class NotificationContainerComponent {
  protected readonly notifications = inject(NotificationService);

  protected dismissToast(id: number): void {
    this.notifications.dismissToast(id);
  }

  protected resolveModal(value: boolean): void {
    this.notifications.resolveModal(value);
  }

  protected styles(variant: AlertVariant): VariantStyle {
    return VARIANT_STYLES.find((entry) => entry.variant === variant)!;
  }
}
