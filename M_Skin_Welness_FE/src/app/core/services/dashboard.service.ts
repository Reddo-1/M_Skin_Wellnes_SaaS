import { Injectable, inject, signal } from '@angular/core';
import { DashboardSummary } from '../models/dashboard.model';
import { ApiService } from './api.service';

@Injectable({ providedIn: 'root' })
export class DashboardService {
  private readonly api = inject(ApiService);

  readonly summary = signal<DashboardSummary | null>(null);

  async fetchSummary(): Promise<DashboardSummary> {
    const data = await this.api.getResource<DashboardSummary>('/dashboard');
    this.summary.set(data);
    return data;
  }
}
