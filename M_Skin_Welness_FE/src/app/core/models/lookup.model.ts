export interface LookupItem {
  id: number;
  name: string;
}

export interface LookupsResponse {
  session_statuses: LookupItem[];
  absence_types: LookupItem[];
  stock_movement_types: LookupItem[];
  skin_types: LookupItem[];
  variations: LookupItem[];
  roles: LookupItem[];
}
