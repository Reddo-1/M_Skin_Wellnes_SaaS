import { LookupItem } from './lookup.model';

export interface AppointmentSummary {
  id: number;
  center_id: number;
  starts_at: string | null;
  ends_at: string | null;
  actual_duration_minutes: number | null;
  booking_source: string | null;
  reserved_price: string | null;
  cancelled_at: string | null;
  notes: string | null;
  status?: LookupItem;
  treatment?: { id: number; name: string; duration_minutes: number; price: string };
  room?: { id: number; name: string };
  machine?: { id: number; name: string } | null;
  client?: { id: number; name: string; email: string | null };
  worker?: { id: number; name: string };
  assistants?: { id: number; name: string; notes: string | null }[];
  created_at: string | null;
  updated_at: string | null;
}
