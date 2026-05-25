import { Component, inject } from '@angular/core';
import { NotificationService } from '../../core/services/notification.service';
import { AlertComponent, AlertVariant } from '../../shared/ui/alert/alert.component';
import { ModalComponent } from '../../shared/ui/modal/modal.component';

const VARIANT_RING: Record<AlertVariant, string> = {
  success: 'fill-success-50',
  error: 'fill-error-50',
  warning: 'fill-warning-50',
  info: 'fill-brand-50',
};

const VARIANT_ICON: Record<AlertVariant, string> = {
  success: 'fill-success-600',
  error: 'fill-error-600',
  warning: 'fill-warning-500',
  info: 'fill-brand-500',
};

const VARIANT_BUTTON: Record<AlertVariant, string> = {
  success: 'bg-success-500 hover:bg-success-600',
  error: 'bg-error-500 hover:bg-error-600',
  warning: 'bg-warning-500 hover:bg-warning-700',
  info: 'bg-brand-500 hover:bg-brand-600',
};

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

  protected ringClass(variant: AlertVariant): string {
    return VARIANT_RING[variant];
  }

  protected iconClass(variant: AlertVariant): string {
    return VARIANT_ICON[variant];
  }

  protected buttonClass(variant: AlertVariant): string {
    return VARIANT_BUTTON[variant];
  }
}
