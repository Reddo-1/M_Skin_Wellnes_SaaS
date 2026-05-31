import { Injectable, inject } from '@angular/core';
import { WorkerSchedule } from '../models/worker-schedule.model';
import { Paginated } from '../models/paginated.model';
import { ApiService } from './api.service';

export interface WorkerScheduleData {
  worker_id: number;
  weekday: number;
  time_slot_id: number;
  start_date: string;
  end_date: string | null;
}

@Injectable({ providedIn: 'root' })
export class WorkerScheduleService {
  private readonly api = inject(ApiService);

  async list(workerId: number): Promise<Paginated<WorkerSchedule>> {
    return await this.api.getCollection<WorkerSchedule>('/worker-schedules', { worker_id: workerId });
  }

  //horarios del centro (overlay del cuadrante); acotado por weekday para no toparse con el limite de pagina
  async listCenter(weekday?: number): Promise<WorkerSchedule[]> {
    const page = await this.api.getCollection<WorkerSchedule>(
      '/worker-schedules',
      weekday === undefined ? undefined : { weekday },
    );
    return page.data;
  }

  async create(data: WorkerScheduleData): Promise<WorkerSchedule> {
    return await this.api.postResource<WorkerSchedule>('/worker-schedules', data);
  }

  async update(id: number, data: WorkerScheduleData): Promise<WorkerSchedule> {
    return await this.api.putResource<WorkerSchedule>(`/worker-schedules/${id}`, data);
  }

  async delete(id: number): Promise<void> {
    await this.api.delete(`/worker-schedules/${id}`);
  }
}
