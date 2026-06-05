import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { environment } from '../../../environments/environment';

export interface PublicPlan {
  id: number;
  code: string;
  name: string;
  description: string | null;
  monthly_price: number;
  max_workers: number;
  allows_online_clients: boolean;
  allows_emails: boolean;
  allows_public_page: boolean;
}

export interface RegisterCenterData {
  admin: {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
  };
  center: {
    name: string;
    slug: string;
  };
  plan_code: string;
}

@Injectable({ providedIn: 'root' })
export class RegistrationService {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = environment.apiUrl;

  async publicPlans(): Promise<PublicPlan[]> {
    const response = await firstValueFrom(
      this.http.get<{ data: PublicPlan[] }>(`${this.baseUrl}/plans`),
    );
    return response.data;
  }

  async register(data: RegisterCenterData): Promise<string> {
    const response = await firstValueFrom(
      this.http.post<{ checkout_url: string }>(`${this.baseUrl}/centers/register`, data),
    );
    return response.checkout_url;
  }
}
