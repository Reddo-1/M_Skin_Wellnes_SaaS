import { Injectable, inject } from '@angular/core';
import { AppointmentSummary } from '../models/appointment.model';
import { ApiService } from './api.service';

@Injectable({ providedIn: 'root' })
export class AppointmentService {
  private readonly api = inject(ApiService);

  async listByClient(clientId: number): Promise<AppointmentSummary[]> {
    const page = await this.api.getCollection<AppointmentSummary>('/appointments', {
      client_id: clientId,
    });
    return page.data;
  }
}
