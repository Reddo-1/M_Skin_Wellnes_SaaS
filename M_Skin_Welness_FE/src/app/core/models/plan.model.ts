export interface Plan {
  id: number;
  code: string;
  name: string;
  description: string | null;
  max_workers: number | null;
  allows_online_clients: boolean;
  allows_emails: boolean;
  allows_public_page: boolean;
  is_active: boolean;
  created_at: string | null;
  updated_at: string | null;
}
