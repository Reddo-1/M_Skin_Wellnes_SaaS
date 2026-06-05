import { WorkerRef } from './worker-absence.model';

export interface WorkerExtraAvailability {
  id: number;
  center_id: number;
  worker_id: number;
  date: string | null;
  start_time: string;
  end_time: string;
  reason: string | null;
  worker?: WorkerRef;
  created_at: string | null;
}
