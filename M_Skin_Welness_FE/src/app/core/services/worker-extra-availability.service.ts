import { Injectable, inject } from '@angular/core';
import { WorkerExtraAvailability } from '../models/worker-extra-availability.model';
import { Paginated } from '../models/paginated.model';
import { ApiService } from './api.service';

export interface WorkerExtraAvailabilityData {
  worker_id: number;
  date: string;
  start_time: string;
  end_time: string;
  reason: string | null;
}

@Injectable({ providedIn: 'root' })
export class WorkerExtraAvailabilityService {
  private readonly api = inject(ApiService);

  async list(workerId: number): Promise<Paginated<WorkerExtraAvailability>> {
    return await this.api.getCollection<WorkerExtraAvailability>('/worker-extra-availabilities', {
      worker_id: workerId,
    });
  }

  async create(data: WorkerExtraAvailabilityData): Promise<WorkerExtraAvailability> {
    return await this.api.postResource<WorkerExtraAvailability>('/worker-extra-availabilities', data);
  }

  async update(id: number, data: WorkerExtraAvailabilityData): Promise<WorkerExtraAvailability> {
    return await this.api.putResource<WorkerExtraAvailability>(
      `/worker-extra-availabilities/${id}`,
      data,
    );
  }

  async delete(id: number): Promise<void> {
    await this.api.delete(`/worker-extra-availabilities/${id}`);
  }
}
