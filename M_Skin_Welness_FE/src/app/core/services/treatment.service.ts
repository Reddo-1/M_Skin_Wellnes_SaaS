import { Injectable, inject } from '@angular/core';
import { Treatment } from '../models/treatment.model';
import { Paginated } from '../models/paginated.model';
import { ApiService, QueryParams } from './api.service';

export interface TreatmentData {
  name: string;
  duration_minutes: number;
  price: number;
  is_active: boolean;
  machine_ids: number[];
  role_ids: number[];
}

@Injectable({ providedIn: 'root' })
export class TreatmentService {
  private readonly api = inject(ApiService);

  async list(filters: { is_active?: boolean; page?: number }): Promise<Paginated<Treatment>> {
    const params: QueryParams = {};
    if (filters.is_active !== undefined) {
      params['is_active'] = filters.is_active;
    }
    if (filters.page !== undefined) {
      params['page'] = filters.page;
    }
    return await this.api.getCollection<Treatment>('/treatments', params);
  }

  async create(data: TreatmentData): Promise<Treatment> {
    return await this.api.postResource<Treatment>('/treatments', data);
  }

  async update(id: number, data: TreatmentData): Promise<Treatment> {
    return await this.api.putResource<Treatment>(`/treatments/${id}`, data);
  }

  async setActive(id: number, isActive: boolean): Promise<Treatment> {
    return await this.api.putResource<Treatment>(`/treatments/${id}`, { is_active: isActive });
  }

  async delete(id: number): Promise<void> {
    await this.api.delete(`/treatments/${id}`);
  }
}
