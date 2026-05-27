import { Injectable, inject } from '@angular/core';
import { SkinEvaluationSummary } from '../models/skin-evaluation.model';
import { ApiService } from './api.service';

export interface CreateSkinEvaluationData {
  client_profile_id: number;
  skin_type_id: number;
  evaluation_date?: string;
  general_notes: string | null;
  variation_ids: number[];
}

export interface UpdateSkinEvaluationData {
  skin_type_id?: number;
  evaluation_date?: string;
  general_notes?: string | null;
  variation_ids?: number[];
}

@Injectable({ providedIn: 'root' })
export class SkinEvaluationService {
  private readonly api = inject(ApiService);

  async listByUser(userId: number): Promise<SkinEvaluationSummary[]> {
    const page = await this.api.getCollection<SkinEvaluationSummary>('/skin-evaluations', {
      user_id: userId,
    });
    return page.data;
  }

  async create(data: CreateSkinEvaluationData): Promise<SkinEvaluationSummary> {
    return await this.api.postResource<SkinEvaluationSummary>('/skin-evaluations', data);
  }

  async update(
    evaluationId: number,
    data: UpdateSkinEvaluationData,
  ): Promise<SkinEvaluationSummary> {
    return await this.api.putResource<SkinEvaluationSummary>(
      `/skin-evaluations/${evaluationId}`,
      data,
    );
  }
}
