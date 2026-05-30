import { Injectable, inject } from '@angular/core';
import { Machine } from '../models/machine.model';
import { Paginated } from '../models/paginated.model';
import { ApiService, QueryParams } from './api.service';

export interface MachineData {
  name: string;
  is_mobile: boolean;
  fixed_room_id: number | null;
  is_active: boolean;
}

@Injectable({ providedIn: 'root' })
export class MachineService {
  private readonly api = inject(ApiService);

  async list(filters: { is_active?: boolean; page?: number }): Promise<Paginated<Machine>> {
    const params: QueryParams = {};
    if (filters.is_active !== undefined) {
      params['is_active'] = filters.is_active;
    }
    if (filters.page !== undefined) {
      params['page'] = filters.page;
    }
    return await this.api.getCollection<Machine>('/machines', params);
  }

  async create(data: MachineData): Promise<Machine> {
    return await this.api.postResource<Machine>('/machines', data);
  }

  async update(id: number, data: MachineData): Promise<Machine> {
    return await this.api.putResource<Machine>(`/machines/${id}`, data);
  }

  async setActive(id: number, isActive: boolean): Promise<Machine> {
    return await this.api.putResource<Machine>(`/machines/${id}`, { is_active: isActive });
  }

  async delete(id: number): Promise<void> {
    await this.api.delete(`/machines/${id}`);
  }
}
