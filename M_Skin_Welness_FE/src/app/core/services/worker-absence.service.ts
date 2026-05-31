import { Injectable, inject } from '@angular/core';
import { WorkerAbsence } from '../models/worker-absence.model';
import { Paginated } from '../models/paginated.model';
import { ApiService } from './api.service';

export interface WorkerAbsenceCreateData {
  worker_id: number;
  from: string;
  to: string;
  is_full_day: boolean;
  start_time: string | null;
  end_time: string | null;
  reason: string | null;
  absence_type_id: number | null;
  notes: string | null;
}

export interface WorkerAbsenceUpdateData {
  date: string;
  is_full_day: boolean;
  start_time: string | null;
  end_time: string | null;
  reason: string | null;
  absence_type_id: number | null;
  notes: string | null;
}

@Injectable({ providedIn: 'root' })
export class WorkerAbsenceService {
  private readonly api = inject(ApiService);

  async list(workerId: number): Promise<Paginated<WorkerAbsence>> {
    return await this.api.getCollection<WorkerAbsence>('/worker-absences', { worker_id: workerId });
  }

  //ausencias del centro en un rango de fechas (overlay del cuadrante)
  async listCenter(from: string, to: string): Promise<WorkerAbsence[]> {
    const page = await this.api.getCollection<WorkerAbsence>('/worker-absences', { from, to });
    return page.data;
  }

  async create(data: WorkerAbsenceCreateData): Promise<WorkerAbsence[]> {
    return await this.api.postResource<WorkerAbsence[]>('/worker-absences', data);
  }

  async update(id: number, data: WorkerAbsenceUpdateData): Promise<WorkerAbsence> {
    return await this.api.putResource<WorkerAbsence>(`/worker-absences/${id}`, data);
  }

  async delete(id: number): Promise<void> {
    await this.api.delete(`/worker-absences/${id}`);
  }
}
