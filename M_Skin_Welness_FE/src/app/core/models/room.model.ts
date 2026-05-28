import { LookupItem } from './lookup.model';

export interface GridPosition {
  x: number;
  y: number;
  w: number;
  h: number;
}

export interface Room {
  id: number;
  center_id: number;
  name: string;
  grid_position: GridPosition | null;
  is_active: boolean;
  machines: LookupItem[];
  created_at: string | null;
}
