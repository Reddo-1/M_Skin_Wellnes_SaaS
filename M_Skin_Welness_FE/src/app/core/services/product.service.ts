import { Injectable, inject } from '@angular/core';
import { Product } from '../models/product.model';
import { Paginated } from '../models/paginated.model';
import { ApiService, QueryParams } from './api.service';

export interface ProductData {
  name: string;
  description: string | null;
  sale_price: number | null;
  cost_price: number | null;
  doses_per_package: number;
  minimum_stock: number;
  is_sellable: boolean;
  is_active: boolean;
}

@Injectable({ providedIn: 'root' })
export class ProductService {
  private readonly api = inject(ApiService);

  async list(filters: {
    is_active?: boolean;
    is_sellable?: boolean;
    search?: string;
    page?: number;
  }): Promise<Paginated<Product>> {
    const params: QueryParams = {};
    if (filters.is_active !== undefined) params['is_active'] = filters.is_active;
    if (filters.is_sellable !== undefined) params['is_sellable'] = filters.is_sellable;
    if (filters.search !== undefined && filters.search !== '') params['search'] = filters.search;
    if (filters.page !== undefined) params['page'] = filters.page;
    return await this.api.getCollection<Product>('/products', params);
  }

  async getById(id: number): Promise<Product> {
    return await this.api.getResource<Product>(`/products/${id}`);
  }

  async create(data: ProductData): Promise<Product> {
    return await this.api.postResource<Product>('/products', data);
  }

  async update(id: number, data: ProductData): Promise<Product> {
    return await this.api.putResource<Product>(`/products/${id}`, data);
  }

  async setActive(id: number, isActive: boolean): Promise<Product> {
    return await this.api.putResource<Product>(`/products/${id}`, { is_active: isActive });
  }

  async delete(id: number): Promise<void> {
    await this.api.delete(`/products/${id}`);
  }
}
