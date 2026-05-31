export interface WorkerScheduleWorker {
  id: number;
  name: string;
}

export interface WorkerScheduleTimeSlot {
  id: number;
  name: string | null;
  start_time: string;
  end_time: string;
  break_start: string | null;
  break_end: string | null;
}

export interface WorkerSchedule {
  id: number;
  center_id: number;
  worker_id: number;
  weekday: number;
  time_slot_id: number;
  start_date: string | null;
  end_date: string | null;
  worker?: WorkerScheduleWorker;
  time_slot?: WorkerScheduleTimeSlot;
  created_at: string | null;
}
