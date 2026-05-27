import { BodyType, SkinEvaluationSummary } from './skin-evaluation.model';

export interface ClinicalRecordSummary {
  id: number;
  center_id: number;
  user_id: number;
  body_type: BodyType;
  current_skin_evaluation_id: number | null;
  general_notes: string | null;
  current_evaluation?: SkinEvaluationSummary | null;
  created_at: string | null;
  updated_at: string | null;
}
