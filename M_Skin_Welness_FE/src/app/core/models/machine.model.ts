import { LookupItem } from './lookup.model';

export interface Machine {
  id: number;
  center_id: number;
  name: string;
  is_mobile: boolean;
  fixed_room_id: number | null;
  is_active: boolean;
  fixed_room: LookupItem | null;
  treatments: LookupItem[];
  created_at: string | null;
}
