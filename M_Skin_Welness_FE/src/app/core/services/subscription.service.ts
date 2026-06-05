import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { environment } from '../../../environments/environment';
import { SubscriptionInvoice, SubscriptionSummary } from '../models/subscription.model';

@Injectable({ providedIn: 'root' })
export class SubscriptionService {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = environment.apiUrl;

  async show(): Promise<SubscriptionSummary> {
    return await firstValueFrom(
      this.http.get<SubscriptionSummary>(`${this.baseUrl}/subscription`),
    );
  }

  async invoices(): Promise<SubscriptionInvoice[]> {
    const response = await firstValueFrom(
      this.http.get<{ data: SubscriptionInvoice[] }>(`${this.baseUrl}/subscription/invoices`),
    );
    return response.data;
  }

  async invoicePdf(invoiceId: string): Promise<Blob> {
    return await firstValueFrom(
      this.http.get(`${this.baseUrl}/subscription/invoices/${invoiceId}/pdf`, {
        responseType: 'blob',
      }),
    );
  }

  async portalUrl(): Promise<string> {
    const response = await firstValueFrom(
      this.http.post<{ portal_url: string }>(`${this.baseUrl}/subscription/portal`, {}),
    );
    return response.portal_url;
  }
}
