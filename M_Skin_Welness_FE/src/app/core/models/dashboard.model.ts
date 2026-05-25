export interface TodayAppointment {
  id: number;
  starts_at: string;
  ends_at: string;
  treatment_name: string | null;
  room_name: string | null;
  client_name: string | null;
  worker_name: string | null;
  status_name: string | null;
}

export interface AppointmentsTodaySummary {
  total: number;
  in_progress: number;
  list: TodayAppointment[];
}

export interface RevenueSummary {
  today: number;
  this_month: number;
  last_month: number;
}

export interface LowStockItem {
  product_id: number;
  product_name: string | null;
  current_quantity: number;
  minimum_stock: number;
}

export interface MonthlyMetricsSummary {
  new_clients: number;
  completed_sessions: number;
}

export interface DashboardSummary {
  appointments_today: AppointmentsTodaySummary;
  revenue: RevenueSummary;
  low_stock: LowStockItem[];
  monthly_metrics: MonthlyMetricsSummary;
}
