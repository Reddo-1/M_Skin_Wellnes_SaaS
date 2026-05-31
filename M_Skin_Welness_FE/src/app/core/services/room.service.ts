import { Injectable, inject } from '@angular/core';
import { Room } from '../models/room.model';
import { Paginated } from '../models/paginated.model';
import { ApiService, QueryParams } from './api.service';

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
}
