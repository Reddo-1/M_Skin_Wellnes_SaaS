import { Injectable, inject } from '@angular/core';
import { Room } from '../models/room.model';
import { Paginated } from '../models/paginated.model';
import { ApiService, QueryParams } from './api.service';

export interface RoomData {
  name: string;
  is_active: boolean;
}

@Injectable({ providedIn: 'root' })
export class RoomService {
  private readonly api = inject(ApiService);

  async list(filters: {
    is_active?: boolean;
    page?: number;
    per_page?: number;
  }): Promise<Paginated<Room>> {
    const params: QueryParams = {};
    if (filters.is_active !== undefined) {
      params['is_active'] = filters.is_active;
    }
    if (filters.page !== undefined) {
      params['page'] = filters.page;
    }
    if (filters.per_page !== undefined) {
      params['per_page'] = filters.per_page;
    }
    return await this.api.getCollection<Room>('/rooms', params);
  }

  async create(data: RoomData): Promise<Room> {
    return await this.api.postResource<Room>('/rooms', data);
  }

  async update(id: number, data: RoomData): Promise<Room> {
    return await this.api.putResource<Room>(`/rooms/${id}`, data);
  }

  async setActive(id: number, isActive: boolean): Promise<Room> {
    return await this.api.putResource<Room>(`/rooms/${id}`, { is_active: isActive });
  }

  async delete(id: number): Promise<void> {
    await this.api.delete(`/rooms/${id}`);
  }
}
