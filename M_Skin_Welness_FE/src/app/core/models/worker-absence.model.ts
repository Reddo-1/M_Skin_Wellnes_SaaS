export interface WorkerAbsenceWorker {
  id: number;
  name: string;
}

export interface WorkerAbsenceType {
  id: number;
  name: string;
}

export interface WorkerAbsence {
  id: number;
  center_id: number;
  worker_id: number;
  date: string | null;
  start_time: string | null;
  end_time: string | null;
  is_full_day: boolean;
  reason: string | null;
  absence_type_id: number | null;
  notes: string | null;
  worker?: WorkerAbsenceWorker;
  absence_type?: WorkerAbsenceType | null;
  created_at: string | null;
  updated_at: string | null;
}
