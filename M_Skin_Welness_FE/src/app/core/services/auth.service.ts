import { HttpClient } from '@angular/common/http';
import { Injectable, computed, inject, signal } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { environment } from '../../../environments/environment';
import { LoginCredentials, LoginResponse } from '../models/auth.model';
import { User, UserRole } from '../models/user.model';

//Constantes con nombres para localstorage.
const TOKEN_AUTH = 'mskin.auth.token';
const USER = 'mskin.auth.user';
const IMPERSONATION_CENTER_ID = 'mskin.impersonation.center_id';

@Injectable({ providedIn: 'root' })
export class AuthService {
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
  //    /login
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
  //    /logout
  async logout(): Promise<void> {
    if (this.token() === null) {
      this.clearSession();
      return;
    }
    try {
      await firstValueFrom(this.http.post(`${this.apiUrl}/logout`, {}));
    } finally {
      this.clearSession();
    }
  }

  //    /me — el back devuelve UserResource::make() directamente, asi que llega envuelto en {data: ...}
  async fetchMe(): Promise<User> {
    const response = await firstValueFrom(
      this.http.get<{ data: User }>(`${this.apiUrl}/me`),
    );
    const user = response.data;
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
    if(!Number.isInteger(value) || value<=0){
      return null;
    }
    return value;
  }
}
