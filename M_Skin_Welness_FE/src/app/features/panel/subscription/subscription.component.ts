import { DatePipe } from '@angular/common';
import { Component, inject, signal } from '@angular/core';
import { SubscriptionService } from '../../../core/services/subscription.service';
import { NotificationService } from '../../../core/services/notification.service';
import { SubscriptionInvoice, SubscriptionStatus, SubscriptionSummary } from '../../../core/models/subscription.model';
import { apiError } from '../../../core/utils/form.util';
import { AlertComponent } from '../../../shared/ui/alert/alert.component';
import { IconComponent } from '../../../shared/ui/icon/icon.component';
import { TableScrollHintComponent } from '../../../shared/ui/table-scroll-hint/table-scroll-hint.component';

const STATUS_LABELS: Record<SubscriptionStatus, string> = {
  active: 'Activa',
  trialing: 'En periodo de prueba',
  past_due: 'Pago pendiente',
  unpaid: 'Impagada',
  canceled: 'Cancelada',
  incomplete: 'Incompleta',
  incomplete_expired: 'Expirada',
};

const INVOICE_STATUS_LABELS: Record<string, string> = {
  paid: 'Pagada',
  open: 'Pendiente',
  void: 'Anulada',
  draft: 'Borrador',
  uncollectible: 'Incobrable',
};

@Component({
  selector: 'app-subscription',
  standalone: true,
  imports: [DatePipe, AlertComponent, IconComponent, TableScrollHintComponent],
  templateUrl: './subscription.component.html',
})
export class SubscriptionComponent {
  private readonly subscriptions = inject(SubscriptionService);
  private readonly notifications = inject(NotificationService);

  protected readonly summary = signal<SubscriptionSummary | null>(null);
  protected readonly invoices = signal<SubscriptionInvoice[]>([]);
  protected readonly loading = signal(false);
  protected readonly errorMessage = signal<string | null>(null);
  protected readonly invoicesError = signal<string | null>(null);

  protected readonly openingPortal = signal(false);
  protected readonly downloadingId = signal<string | null>(null);

  constructor() {
    void this.load();
  }

  protected async load(): Promise<void> {
    this.loading.set(true);
    this.errorMessage.set(null);
    this.invoicesError.set(null);
    try {
      const summary = await this.subscriptions.show();
      this.summary.set(summary);

      if (summary.has_subscription) {
        try {
          this.invoices.set(await this.subscriptions.invoices());
        } catch {
          this.invoicesError.set('No se han podido cargar las facturas. Vuelve a intentarlo en unos segundos.');
        }
      }
    } catch (error) {
      this.errorMessage.set(apiError(error));
    } finally {
      this.loading.set(false);
    }
  }

  async openPortal(): Promise<void> {
    if (this.openingPortal()) return;
    this.openingPortal.set(true);
    try {
      const url = await this.subscriptions.portalUrl();
      window.location.href = url;
    } catch (error) {
      this.notifications.toast.error(apiError(error));
      this.openingPortal.set(false);
    }
  }

  async downloadInvoice(invoice: SubscriptionInvoice): Promise<void> {
    if (this.downloadingId() !== null) return;
    this.downloadingId.set(invoice.id);
    try {
      const blob = await this.subscriptions.invoicePdf(invoice.id);
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = `factura-${invoice.number ?? invoice.id}.pdf`;
      link.click();
      URL.revokeObjectURL(url);
    } catch (error) {
      this.notifications.toast.error(apiError(error));
    } finally {
      this.downloadingId.set(null);
    }
  }

  protected statusLabel(status: SubscriptionStatus | undefined): string {
    if (status === undefined) return '—';
    return STATUS_LABELS[status] ?? status;
  }

  protected statusTone(status: SubscriptionStatus | undefined): 'success' | 'warning' | 'danger' | 'neutral' {
    switch (status) {
      case 'active':
      case 'trialing':
        return 'success';
      case 'past_due':
      case 'incomplete':
        return 'warning';
      case 'unpaid':
      case 'canceled':
      case 'incomplete_expired':
        return 'danger';
      default:
        return 'neutral';
    }
  }

  protected invoiceStatusLabel(status: string): string {
    return INVOICE_STATUS_LABELS[status] ?? status;
  }
}
