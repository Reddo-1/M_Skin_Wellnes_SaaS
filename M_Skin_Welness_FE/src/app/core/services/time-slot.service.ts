import { Injectable, inject } from '@angular/core';
import { TimeSlot } from '../models/time-slot.model';
import { Paginated } from '../models/paginated.model';
import { ApiService, QueryParams } from './api.service';

export interface TimeSlotData {
  name: string | null;
  start_time: string;
  end_time: string;
  break_start: string | null;
  break_end: string | null;
  is_active: boolean;
}

@Injectable({ providedIn: 'root' })
export class TimeSlotService {
  private readonly api = inject(ApiService);

  async list(filters: { is_active?: boolean; page?: number }): Promise<Paginated<TimeSlot>> {
    const params: QueryParams = {};
    if (filters.is_active !== undefined) {
      params['is_active'] = filters.is_active;
    }
    if (filters.page !== undefined) {
      params['page'] = filters.page;
    }
    return await this.api.getCollection<TimeSlot>('/time-slots', params);
  }

  async create(data: TimeSlotData): Promise<TimeSlot> {
    return await this.api.postResource<TimeSlot>('/time-slots', data);
  }

  async update(id: number, data: TimeSlotData): Promise<TimeSlot> {
    return await this.api.putResource<TimeSlot>(`/time-slots/${id}`, data);
  }

  async setActive(id: number, isActive: boolean): Promise<TimeSlot> {
    return await this.api.putResource<TimeSlot>(`/time-slots/${id}`, { is_active: isActive });
  }

  async delete(id: number): Promise<void> {
    await this.api.delete(`/time-slots/${id}`);
  }
}
