import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Paginated } from '../models/paginated.model';

export type QueryParams = Record<string, string | number | boolean>;

@Injectable({ providedIn: 'root' })
export class ApiService {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = environment.apiUrl;

  async getResource<T>(path: string, params?: QueryParams): Promise<T> {
    const response = await firstValueFrom(
      this.http.get<{ data: T }>(this.url(path), { params: this.toHttpParams(params) }),
    );
    return response.data;
  }

  async postResource<T>(path: string, body: unknown): Promise<T> {
    const response = await firstValueFrom(
      this.http.post<{ data: T }>(this.url(path), body),
    );
    return response.data;
  }

  async putResource<T>(path: string, body: unknown): Promise<T> {
    const response = await firstValueFrom(
      this.http.put<{ data: T }>(this.url(path), body),
    );
    return response.data;
  }

  async getCollection<T>(path: string, params?: QueryParams): Promise<Paginated<T>> {
    return await firstValueFrom(
      this.http.get<Paginated<T>>(this.url(path), { params: this.toHttpParams(params) }),
    );
  }

  async postNoContent(path: string, body?: unknown): Promise<void> {
    await firstValueFrom(this.http.post(this.url(path), body ?? {}));
  }

  async delete(path: string): Promise<void> {
    await firstValueFrom(this.http.delete(this.url(path)));
  }

  async postForm<T>(path: string, form: FormData): Promise<T> {
    const response = await firstValueFrom(
      this.http.post<{ data: T }>(this.url(path), form),
    );
    return response.data;
  }

  //Cogiendo la url base de las variables de entorno devuelve la ruta hacia la api
  private url(path: string): string {
    return `${this.baseUrl}${path}`;
  }

  //Helper para paginación de colecciones y/o busquedas.
  private toHttpParams(input?: QueryParams): HttpParams | undefined {
    if (input === undefined) return undefined;
    let params = new HttpParams();
    for (const [key, value] of Object.entries(input)) {
      params = params.set(key, String(value));
    }
    return params;
  }
}
