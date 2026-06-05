import { LookupItem } from './lookup.model';

export type BodyType = 'facial' | 'corporal';

export interface ClinicalImage {
  id: number;
  category: string;
  url: string;
}

export interface SkinEvaluationSummary {
  id: number;
  center_id: number;
  user_id: number;
  client_profile_id: number;
  skin_type_id: number;
  evaluation_date: string | null;
  professional_id: number;
  general_notes: string | null;
  client_profile?: { id: number; body_type: BodyType };
  skin_type?: LookupItem;
  professional?: { id: number; name: string };
  variations?: LookupItem[];
  clinical_images?: ClinicalImage[];
  created_at: string | null;
  updated_at: string | null;
}
