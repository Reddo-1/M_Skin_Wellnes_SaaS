export interface TimeSlot {
  id: number;
  center_id: number;
  name: string | null;
  start_time: string;
  end_time: string;
  break_start: string | null;
  break_end: string | null;
  is_active: boolean;
}
