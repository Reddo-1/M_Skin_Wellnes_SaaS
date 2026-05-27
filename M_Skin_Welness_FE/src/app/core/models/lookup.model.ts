export interface LookupItem {
  id: number;
  name: string;
}

export interface SessionStatusLookup extends LookupItem {
  sort_order?: number;
}

export interface LookupsResponse {
  session_statuses: SessionStatusLookup[];
  absence_types: LookupItem[];
  payment_methods: LookupItem[];
  sale_statuses: LookupItem[];
  stock_movement_types: LookupItem[];
  skin_types: LookupItem[];
  variations: LookupItem[];
  roles: LookupItem[];
}
