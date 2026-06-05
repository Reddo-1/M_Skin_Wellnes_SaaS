import { HttpClient } from '@angular/common/http';
import { Injectable, inject, signal } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { environment } from '../../../environments/environment';
import { LookupItem, LookupsResponse } from '../models/lookup.model';

@Injectable({ providedIn: 'root' })
export class LookupService {
  private readonly http = inject(HttpClient);

  readonly sessionStatuses = signal<LookupItem[]>([]);
  readonly absenceTypes = signal<LookupItem[]>([]);
  readonly stockMovementTypes = signal<LookupItem[]>([]);
  readonly skinTypes = signal<LookupItem[]>([]);
  readonly variations = signal<LookupItem[]>([]);
  readonly roles = signal<LookupItem[]>([]);

  constructor() {
    void this.fetch();
  }

  private async fetch(): Promise<void> {
    try {
      const data = await firstValueFrom(
        this.http.get<LookupsResponse>(`${environment.apiUrl}/lookups`),
      );
      this.sessionStatuses.set(data.session_statuses);
      this.absenceTypes.set(data.absence_types);
      this.stockMovementTypes.set(data.stock_movement_types);
      this.skinTypes.set(data.skin_types);
      this.variations.set(data.variations);
      this.roles.set(data.roles);
    } catch {
      //los lookups no son bloqueantes; cada consumidor gestiona su propio fallback si llega vacio
    }
  }
}
