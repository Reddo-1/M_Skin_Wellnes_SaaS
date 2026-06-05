import { Injectable, inject } from '@angular/core';
import { User } from '../models/user.model';
import { ApiService } from './api.service';

export interface UpdateProfileData {
  name?: string;
  email?: string;
  phone?: string | null;
  birth_date?: string | null;
}

@Injectable({ providedIn: 'root' })
export class UserService {
  private readonly api = inject(ApiService);

  async update(userId: number, data: UpdateProfileData): Promise<User> {
    return await this.api.putResource<User>(`/users/${userId}`, data);
  }

  async changePassword(userId: number, password: string, passwordConfirmation: string): Promise<void> {
    await this.api.postNoContent(`/users/${userId}/password`, {
      password,
      password_confirmation: passwordConfirmation,
    });
  }

  async uploadAvatar(userId: number, file: File): Promise<void> {
    const form = new FormData();
    form.append('user_id', String(userId));
    form.append('category', 'foto_perfil');
    form.append('file', file);
    await this.api.postForm('/user-files', form);
  }
}
