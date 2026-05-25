export type UserRole =|'superadmin'|'administrador'|'recepcionista'|
'rrhh'|'diagnosticador'|'dermo_esteticien'|'fisioterapeuta'|'manicurista'|
'cliente';

export interface PlanSummary {
  id: number;
  code: string;
  name: string;
  monthly_price: string;
  annual_price: string;
}

export interface CenterSummary {
  id: number;
  name: string;
  slug: string;
  plan: PlanSummary | null;
}

export interface User {
  id: number;
  center_id: number | null;
  name: string;
  email: string;
  phone: string | null;
  birth_date: string | null;
  is_active: boolean;
  registration_source: 'staff' | 'online' | null;
  roles: UserRole[];
  permissions: string[];
  avatar_url: string | null;
  center?: CenterSummary | null;
  created_at: string | null;
  updated_at: string | null;
}
