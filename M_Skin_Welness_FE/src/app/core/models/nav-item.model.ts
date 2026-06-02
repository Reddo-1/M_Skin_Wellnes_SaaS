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
  | 'building'
  | 'user'
  | 'shield-check'
  | 'sparkles'
  | 'cpu';

export type NavSection = 'operativa' | 'catalogo' | 'clinico' | 'equipo' | 'inventario' | 'cuenta';

export interface NavItem {
  label: string;
  path: string;
  icon: NavIcon;
  section: NavSection;
  allowedRoles: UserRole[];
}
