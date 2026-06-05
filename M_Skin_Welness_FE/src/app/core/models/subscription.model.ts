import { Plan } from './plan.model';

export type SubscriptionStatus =
  | 'active'
  | 'trialing'
  | 'past_due'
  | 'unpaid'
  | 'canceled'
  | 'incomplete'
  | 'incomplete_expired';

export interface SubscriptionSummary {
  has_subscription: boolean;
  id?: string;
  status?: SubscriptionStatus;
  on_trial?: boolean;
  trial_ends_at?: string | null;
  ends_at?: string | null;
  cancel_at_period_end?: boolean;
  current_period_start?: string | null;
  current_period_end?: string | null;
  plan?: Plan | null;
}

export interface SubscriptionInvoice {
  id: string;
  number: string | null;
  date: string;
  total: string;
  status: string;
}
