import { UserRole } from './user.model';

export type NavIcon =|'dashboard'|'calendar'|'map'|'users'|'briefcase'|'clock'|'package'|'archive'|'shopping-bag'|'building'|'user'|'shield-check';

export type NavSection = 'operativa' | 'clinico' | 'equipo' | 'inventario' | 'cuenta';

export interface NavItem {
  label: string;
  path: string;
  icon: NavIcon;
  section: NavSection;
  allowedRoles: UserRole[];
}
