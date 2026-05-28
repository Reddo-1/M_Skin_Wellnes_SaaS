import { Injectable, inject } from '@angular/core';
import { StockMovement } from '../models/stock-movement.model';
import { Paginated } from '../models/paginated.model';
import { ApiService, QueryParams } from './api.service';

export interface StockEntryData {
  product_id: number;
  movement_type_id: number;
  package_quantity: number;
  reason: string | null;
}

@Injectable({ providedIn: 'root' })
export class StockMovementService {
  private readonly api = inject(ApiService);

  async list(filters: {
    product_id?: number;
    movement_type_id?: number;
    user_id?: number;
    from?: string;
    to?: string;
    page?: number;
  }): Promise<Paginated<StockMovement>> {
    const params: QueryParams = {};
    if (filters.product_id !== undefined) params['product_id'] = filters.product_id;
    if (filters.movement_type_id !== undefined) params['movement_type_id'] = filters.movement_type_id;
    if (filters.user_id !== undefined) params['user_id'] = filters.user_id;
    if (filters.from !== undefined && filters.from !== '') params['from'] = filters.from;
    if (filters.to !== undefined && filters.to !== '') params['to'] = filters.to;
    if (filters.page !== undefined) params['page'] = filters.page;
    return await this.api.getCollection<StockMovement>('/stock-movements', params);
  }

  async create(data: StockEntryData): Promise<StockMovement> {
    return await this.api.postResource<StockMovement>('/stock-movements', data);
  }
}
