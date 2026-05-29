export interface TimeSlot {
  id: number;
  center_id: number;
  name: string | null;
  start_time: string;
  end_time: string;
  is_active: boolean;
}
