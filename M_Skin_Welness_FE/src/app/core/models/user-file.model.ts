export interface UserFileSummary {
  id: number;
  user_id: number;
  center_id: number;
  skin_evaluation_id: number | null;
  category: string;
  url: string;
  notes: string | null;
  created_at: string | null;
}
