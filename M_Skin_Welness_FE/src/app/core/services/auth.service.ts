import { HttpClient } from '@angular/common/http';
import { Injectable, computed, inject, signal } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { environment } from '../../../environments/environment';
import { LoginCredentials, LoginResponse } from '../models/auth.model';
import { User, UserRole } from '../models/user.model';
import { ApiService } from './api.service';

//Constantes con nombres para localstorage.
const TOKEN_AUTH = 'mskin.auth.token';
const USER = 'mskin.auth.user';
const IMPERSONATION_CENTER_ID = 'mskin.impersonation.center_id';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly api = inject(ApiService);
  //login devuelve { token, user } (no Resource): se queda con http.post directo
  private readonly http = inject(HttpClient);
  private readonly apiUrl = environment.apiUrl;

  //Variables globales: Token y impersonationCenterId para el interceptor, User para uso general con roles
  readonly token = signal<string | null>(this.readToken());
  readonly user = signal<User | null>(this.readUser());
  readonly impersonationCenterId = signal<number | null>(this.readImpersonationCenterId());

  readonly isAuthenticated = computed(() => this.token() !== null && this.user() !== null);
  readonly roles = computed<UserRole[]>(() => this.user()?.roles ?? []);
  readonly permissions = computed<string[]>(() => this.user()?.permissions ?? []);
  readonly centerId = computed<number | null>(
    () => this.impersonationCenterId() ?? this.user()?.center_id ?? null,
  );
  readonly isImpersonating = computed(() => this.impersonationCenterId() !== null);
  readonly effectiveRoles = computed<UserRole[]>(() => {
    const own = this.roles();
    if (this.isImpersonating() && own.includes('superadmin')) {
      return ['administrador'];
    }
    return own;
  });

  async forgotPassword(email: string): Promise<{ message: string }> {
    return await firstValueFrom(
      this.http.post<{ message: string }>(`${this.apiUrl}/forgot-password`, { email }),
    );
  }

  async resetPassword(payload: {
    token: string;
    email: string;
    password: string;
    password_confirmation: string;
  }): Promise<void> {
    await firstValueFrom(this.http.post(`${this.apiUrl}/reset-password`, payload));
  }

  async login(credentials: LoginCredentials): Promise<LoginResponse> {
    const response = await firstValueFrom(
      this.http.post<LoginResponse>(`${this.apiUrl}/login`, credentials),
    );
    localStorage.setItem(TOKEN_AUTH, response.token);
    localStorage.setItem(USER, JSON.stringify(response.user));
    this.token.set(response.token);
    this.user.set(response.user);
    return response;
  }

  async logout(): Promise<void> {
    if (this.token() === null) {
      this.clearSession();
      return;
    }
    try {
      await this.api.postNoContent('/logout');
    } finally {
      this.clearSession();
    }
  }

  async fetchMe(): Promise<User> {
    const user = await this.api.getResource<User>('/me');
    localStorage.setItem(USER, JSON.stringify(user));
    this.user.set(user);
    return user;
  }

  hasRole(role: UserRole): boolean {
    return this.roles().includes(role);
  }

  hasAnyRole(roles: UserRole[]): boolean {
    const owned = this.roles();
    return roles.some((role) => owned.includes(role));
  }

  hasPermission(permission: string): boolean {
    return this.permissions().includes(permission);
  }

  defaultRouteForRoles(roles: UserRole[]): string {
    if (roles.includes('cliente')) return '/panel/mis-citas';
    if (roles.includes('administrador') || roles.includes('superadmin')) return '/panel/dashboard';
    return '/panel/cuadrante';
  }

  startImpersonation(token: string, centerId: number): void {
    localStorage.setItem(TOKEN_AUTH, token);
    localStorage.setItem(IMPERSONATION_CENTER_ID, String(centerId));
    this.token.set(token);
    this.impersonationCenterId.set(centerId);
  }

  clearSession(): void {
    localStorage.removeItem(TOKEN_AUTH);
    localStorage.removeItem(USER);
    localStorage.removeItem(IMPERSONATION_CENTER_ID);
    this.token.set(null);
    this.user.set(null);
    this.impersonationCenterId.set(null);
  }

  private readToken(): string | null {
    return localStorage.getItem(TOKEN_AUTH);
  }

  private readUser(): User | null {
    const raw = localStorage.getItem(USER);
    if (raw === null) {
      return null;
    }
    try {
      return JSON.parse(raw);
    } catch {
      localStorage.removeItem(USER);
      return null;
    }
  }

  private readImpersonationCenterId(): number | null {
    const raw = localStorage.getItem(IMPERSONATION_CENTER_ID);
    if (raw === null) {
      return null;
    }
    const value = Number(raw);
    if (!Number.isInteger(value) || value <= 0) {
      return null;
    }
    return value;
  }
}
