import { Injectable, inject } from '@angular/core';
import { UserFileSummary } from '../models/user-file.model';
import { ApiService } from './api.service';

export interface UploadUserFileData {
  user_id: number;
  category: string;
  skin_evaluation_id: number | null;
  notes: string | null;
  file: File;
}

@Injectable({ providedIn: 'root' })
export class UserFileService {
  private readonly api = inject(ApiService);

  async listByEvaluation(evaluationId: number): Promise<UserFileSummary[]> {
    const page = await this.api.getCollection<UserFileSummary>('/user-files', {
      skin_evaluation_id: evaluationId,
    });
    return page.data;
  }

  async upload(data: UploadUserFileData): Promise<UserFileSummary> {
    const form = new FormData();
    form.append('user_id', String(data.user_id));
    form.append('category', data.category);
    if (data.skin_evaluation_id !== null) {
      form.append('skin_evaluation_id', String(data.skin_evaluation_id));
    }
    if (data.notes !== null && data.notes !== '') {
      form.append('notes', data.notes);
    }
    form.append('file', data.file);
    return await this.api.postForm<UserFileSummary>('/user-files', form);
  }

  async delete(fileId: number): Promise<void> {
    await this.api.delete(`/user-files/${fileId}`);
  }
}
