import { UserRole } from './user.model';

export type NavIcon =
  | 'dashboard'
  | 'calendar'
  | 'users'
  | 'briefcase'
  | 'clock'
  | 'package'
  | 'archive'
  | 'shopping-bag'
  | 'document'
  | 'building'
  | 'user'
  | 'shield-check'
  | 'sparkles'
  | 'cpu';

export type NavSection = 'operations' | 'catalog' | 'clinical' | 'team' | 'inventory' | 'account';

export interface NavItem {
  label: string;
  path: string;
  icon: NavIcon;
  section: NavSection;
  allowedRoles: UserRole[];
}
