import { HttpClient } from '@angular/common/http';
import { Injectable, inject, signal } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { environment } from '../../../environments/environment';
import { DashboardSummary } from '../models/dashboard.model';

@Injectable({ providedIn: 'root' })
export class DashboardService {
  private readonly http = inject(HttpClient);
  private readonly apiUrl = environment.apiUrl;

  readonly summary = signal<DashboardSummary | null>(null);

  async fetchSummary(): Promise<DashboardSummary> {
    const response = await firstValueFrom(
      this.http.get<{ data: DashboardSummary }>(`${this.apiUrl}/dashboard`),
    );
    this.summary.set(response.data);
    return response.data;
  }
}
