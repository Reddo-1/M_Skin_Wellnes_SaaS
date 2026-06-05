import { LookupItem } from './lookup.model';

export interface TreatmentSummary {
  id: number;
  name: string;
  duration_minutes: number;
  price: string;
  is_active: boolean;
}

export interface Treatment {
  id: number;
  center_id: number;
  name: string;
  duration_minutes: number;
  margin_minutes: number;
  price: string;
  is_active: boolean;
  machines: LookupItem[];
  authorized_roles: LookupItem[];
  created_at: string | null;
  updated_at: string | null;
}
