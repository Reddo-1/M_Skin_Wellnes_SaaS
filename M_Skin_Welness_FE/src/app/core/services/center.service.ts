import { Injectable, inject } from '@angular/core';
import { Center } from '../models/center.model';
import { CenterFile, CenterFileType } from '../models/center-file.model';
import { ApiService } from './api.service';

export interface CenterUpdateData {
  name: string;
}

@Injectable({ providedIn: 'root' })
export class CenterService {
  private readonly api = inject(ApiService);

  async show(id: number): Promise<Center> {
    return await this.api.getResource<Center>(`/centers/${id}`);
  }

  async update(id: number, data: CenterUpdateData): Promise<Center> {
    return await this.api.putResource<Center>(`/centers/${id}`, data);
  }

  async files(type?: CenterFileType): Promise<CenterFile[]> {
    return await this.api.getResource<CenterFile[]>('/center-files', type ? { type } : undefined);
  }

  async uploadFile(type: CenterFileType, file: File): Promise<CenterFile> {
    const form = new FormData();
    form.append('type', type);
    form.append('file', file);
    return await this.api.postForm<CenterFile>('/center-files', form);
  }

  async deleteFile(id: number): Promise<void> {
    await this.api.delete(`/center-files/${id}`);
  }
}
