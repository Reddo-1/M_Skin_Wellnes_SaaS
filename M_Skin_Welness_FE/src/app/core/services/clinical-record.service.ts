import { Injectable, inject } from '@angular/core';
import { ClinicalRecordSummary } from '../models/clinical-record.model';
import { BodyType } from '../models/skin-evaluation.model';
import { ApiService } from './api.service';

export interface CreateClinicalRecordData {
  user_id: number;
  body_type: BodyType;
  general_notes: string | null;
}

export interface UpdateClinicalRecordData {
  general_notes: string | null;
}

@Injectable({ providedIn: 'root' })
export class ClinicalRecordService {
  private readonly api = inject(ApiService);

  async listByUser(userId: number): Promise<ClinicalRecordSummary[]> {
    const page = await this.api.getCollection<ClinicalRecordSummary>('/client-profiles', {
      user_id: userId,
    });
    return page.data;
  }

  async create(data: CreateClinicalRecordData): Promise<ClinicalRecordSummary> {
    return await this.api.postResource<ClinicalRecordSummary>('/client-profiles', data);
  }

  async update(recordId: number, data: UpdateClinicalRecordData): Promise<ClinicalRecordSummary> {
    return await this.api.putResource<ClinicalRecordSummary>(`/client-profiles/${recordId}`, data);
  }
}
