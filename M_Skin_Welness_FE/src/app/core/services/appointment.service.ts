import { Injectable, inject } from '@angular/core';
import { AppointmentSummary } from '../models/appointment.model';
import { ApiService, QueryParams } from './api.service';

export interface AppointmentCreateData {
  treatment_id: number;
  room_id: number;
  client_id: number;
  worker_id: number;
  machine_id: number | null;
  starts_at: string;
  ends_at: string;
  booking_source: string;
  status_id: number;
  reserved_price: number | null;
  notes: string | null;
  assistant_ids: number[];
}

export interface AppointmentUpdateData {
  treatment_id?: number;
  room_id?: number;
  worker_id?: number;
  machine_id?: number | null;
  starts_at?: string;
  ends_at?: string;
  reserved_price?: number | null;
  notes?: string | null;
  assistant_ids?: number[];
}

@Injectable({ providedIn: 'root' })
export class AppointmentService {
  private readonly api = inject(ApiService);

  //el cuadrante pide el rango completo del dia (per_page alto); workerId acota a una columna
  async listRange(from: string, to: string, workerId?: number): Promise<AppointmentSummary[]> {
    const params: QueryParams = { from, to, per_page: 200 };
    if (workerId !== undefined) {
      params['worker_id'] = workerId;
    }
    const page = await this.api.getCollection<AppointmentSummary>('/appointments', params);
    return page.data;
  }

  async listByClient(clientId: number): Promise<AppointmentSummary[]> {
    const page = await this.api.getCollection<AppointmentSummary>('/appointments', {
      client_id: clientId,
    });
    return page.data;
  }

  async create(data: AppointmentCreateData): Promise<AppointmentSummary> {
    return await this.api.postResource<AppointmentSummary>('/appointments', data);
  }

  async update(id: number, data: AppointmentUpdateData): Promise<AppointmentSummary> {
    return await this.api.putResource<AppointmentSummary>(`/appointments/${id}`, data);
  }

  async changeStatus(id: number, statusId: number): Promise<AppointmentSummary> {
    return await this.api.postResource<AppointmentSummary>(`/appointments/${id}/status`, {
      status_id: statusId,
    });
  }

  async delete(id: number): Promise<void> {
    await this.api.delete(`/appointments/${id}`);
  }
}
