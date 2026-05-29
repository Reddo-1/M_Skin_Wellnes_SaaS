import { Injectable, inject } from '@angular/core';
import { User } from '../models/user.model';
import { Paginated } from '../models/paginated.model';
import { ApiService, QueryParams } from './api.service';

export interface CreateWorkerData {
  name: string;
  email: string | null;
  phone: string | null;
  birth_date: string | null;
  role_ids: number[];
  password: string | null;
  is_active: boolean;
}

export interface UpdateWorkerData {
  name: string;
  email: string | null;
  phone: string | null;
  birth_date: string | null;
}

@Injectable({ providedIn: 'root' })
export class WorkerService {
  private readonly api = inject(ApiService);

  async getById(workerId: number): Promise<User> {
    return await this.api.getResource<User>(`/users/${workerId}`);
  }

  async list(filters: {
    search?: string;
    role?: string;
    is_active?: boolean;
    page?: number;
  }): Promise<Paginated<User>> {
    const params: QueryParams = {};
    if (filters.role !== undefined && filters.role !== '') {
      params['role'] = filters.role;
    } else {
      params['staff'] = true;
    }
    if (filters.search !== undefined && filters.search.trim() !== '') {
      params['search'] = filters.search.trim();
    }
    if (filters.is_active !== undefined) {
      params['is_active'] = filters.is_active;
    }
    if (filters.page !== undefined) {
      params['page'] = filters.page;
    }
    return await this.api.getCollection<User>('/users', params);
  }

  async create(data: CreateWorkerData): Promise<User> {
    return await this.api.postResource<User>('/users', data);
  }

  async update(workerId: number, data: UpdateWorkerData): Promise<User> {
    return await this.api.putResource<User>(`/users/${workerId}`, data);
  }

  async activate(workerId: number): Promise<User> {
    return await this.api.postResource<User>(`/users/${workerId}/activate`, {});
  }

  async deactivate(workerId: number): Promise<void> {
    await this.api.delete(`/users/${workerId}`);
  }

  async syncRoles(workerId: number, roleIds: number[]): Promise<User> {
    return await this.api.postResource<User>(`/users/${workerId}/roles`, { role_ids: roleIds });
  }

  async changePassword(workerId: number, password: string, passwordConfirmation: string): Promise<void> {
    await this.api.postNoContent(`/users/${workerId}/password`, {
      password,
      password_confirmation: passwordConfirmation,
    });
  }
}
