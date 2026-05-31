import { Injectable, inject } from '@angular/core';
import { User } from '../models/user.model';
import { Paginated } from '../models/paginated.model';
import { ApiService, QueryParams } from './api.service';

export interface CreateClientData {
  name: string;
  email: string | null;
  phone: string | null;
  birth_date: string | null;
  role_ids: number[];
}

export interface UpdateClientData {
  name: string;
  email: string | null;
  phone: string | null;
  birth_date: string | null;
}

@Injectable({ providedIn: 'root' })
export class ClientService {
  private readonly api = inject(ApiService);

  async getById(userId: number): Promise<User> {
    return await this.api.getResource<User>(`/users/${userId}`);
  }

  async list(filters: {
    search?: string;
    is_active?: boolean;
    page?: number;
    per_page?: number;
  }): Promise<Paginated<User>> {
    const params: QueryParams = { role: 'cliente' };
    if (filters.search !== undefined && filters.search.trim() !== '') {
      params['search'] = filters.search.trim();
    }
    if (filters.is_active !== undefined) {
      params['is_active'] = filters.is_active;
    }
    if (filters.page !== undefined) {
      params['page'] = filters.page;
    }
    if (filters.per_page !== undefined) {
      params['per_page'] = filters.per_page;
    }
    return await this.api.getCollection<User>('/users', params);
  }

  async create(data: CreateClientData): Promise<User> {
    return await this.api.postResource<User>('/users', data);
  }

  async update(userId: number, data: UpdateClientData): Promise<User> {
    return await this.api.putResource<User>(`/users/${userId}`, data);
  }

  async activate(userId: number): Promise<User> {
    return await this.api.postResource<User>(`/users/${userId}/activate`, {});
  }

  async deactivate(userId: number): Promise<void> {
    await this.api.delete(`/users/${userId}`);
  }

  async activateOnlineAccess(userId: number, email: string | null): Promise<User> {
    const body = email !== null && email !== '' ? { email } : {};
    return await this.api.postResource<User>(`/users/${userId}/online-access`, body);
  }
}
