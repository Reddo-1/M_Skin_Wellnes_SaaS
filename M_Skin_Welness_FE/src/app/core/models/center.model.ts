import { Plan } from './plan.model';

export interface Center {
  id: number;
  uuid: string;
  name: string;
  slug: string;
  is_active: boolean;
  plan: Plan | null;
  created_at: string | null;
  updated_at: string | null;
}
