import { Injectable, inject } from '@angular/core';
import { ProductStock } from '../models/product-stock.model';
import { Paginated } from '../models/paginated.model';
import { ApiService, QueryParams } from './api.service';

export interface StockAdjustData {
  new_quantity: number;
  reason: string;
}

@Injectable({ providedIn: 'root' })
export class ProductStockService {
  private readonly api = inject(ApiService);

  async list(filters: {
    product_id?: number;
    below_minimum?: boolean;
    page?: number;
  }): Promise<Paginated<ProductStock>> {
    const params: QueryParams = {};
    if (filters.product_id !== undefined) params['product_id'] = filters.product_id;
    if (filters.below_minimum !== undefined) params['below_minimum'] = filters.below_minimum;
    if (filters.page !== undefined) params['page'] = filters.page;
    return await this.api.getCollection<ProductStock>('/product-stocks', params);
  }

  async adjust(stockId: number, data: StockAdjustData): Promise<ProductStock> {
    return await this.api.postResource<ProductStock>(`/product-stocks/${stockId}/adjust`, data);
  }
}
