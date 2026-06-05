import { Component, computed, inject } from '@angular/core';
import { RouterLink, RouterLinkActive } from '@angular/router';
import { AuthService } from '../../core/services/auth.service';
import { SidebarService } from '../../core/services/sidebar.service';
import { NavItem, NavSection } from '../../core/models/nav-item.model';
import { IconComponent } from '../../shared/ui/icon/icon.component';

const ALL_ITEMS: NavItem[] = [
  {
    label: 'Dashboard',
    path: '/panel/dashboard',
    icon: 'dashboard',
    section: 'operations',
    allowedRoles: ['administrador'],
  },
  {
    label: 'Cuadrante',
    path: '/panel/cuadrante',
    icon: 'calendar',
    section: 'operations',
    allowedRoles: [
      'administrador',
      'recepcionista',
      'rrhh',
      'diagnosticador',
      'dermo_esteticien',
      'fisioterapeuta',
      'manicurista',
    ],
  },
  {
    label: 'Mis citas',
    path: '/panel/mis-citas',
    icon: 'calendar',
    section: 'operations',
    allowedRoles: ['cliente'],
  },
  {
    label: 'Tratamientos',
    path: '/panel/tratamientos',
    icon: 'sparkles',
    section: 'catalog',
    allowedRoles: [
      'administrador',
      'recepcionista',
      'rrhh',
      'diagnosticador',
      'dermo_esteticien',
      'fisioterapeuta',
      'manicurista',
    ],
  },
  {
    label: 'Máquinas',
    path: '/panel/maquinas',
    icon: 'cpu',
    section: 'catalog',
    allowedRoles: [
      'administrador',
      'recepcionista',
      'rrhh',
      'diagnosticador',
      'dermo_esteticien',
      'fisioterapeuta',
      'manicurista',
    ],
  },
  {
    label: 'Salas',
    path: '/panel/salas',
    icon: 'building',
    section: 'catalog',
    allowedRoles: [
      'administrador',
      'recepcionista',
      'rrhh',
      'diagnosticador',
      'dermo_esteticien',
      'fisioterapeuta',
      'manicurista',
    ],
  },
  {
    label: 'Clientes',
    path: '/panel/clientes',
    icon: 'users',
    section: 'clinical',
    allowedRoles: [
      'administrador',
      'recepcionista',
      'diagnosticador',
      'dermo_esteticien',
      'fisioterapeuta',
      'manicurista',
    ],
  },
  {
    label: 'Mi consentimiento',
    path: '/panel/mi-consent',
    icon: 'shield-check',
    section: 'clinical',
    allowedRoles: ['cliente'],
  },
  {
    label: 'Trabajadores',
    path: '/panel/trabajadores',
    icon: 'briefcase',
    section: 'team',
    allowedRoles: ['administrador', 'rrhh'],
  },
  {
    label: 'Franjas horarias',
    path: '/panel/time-slots',
    icon: 'clock',
    section: 'team',
    allowedRoles: ['administrador', 'rrhh'],
  },
  {
    label: 'Productos',
    path: '/panel/productos',
    icon: 'package',
    section: 'inventory',
    allowedRoles: [
      'administrador',
      'recepcionista',
      'diagnosticador',
      'dermo_esteticien',
      'fisioterapeuta',
      'manicurista',
      'cliente',
    ],
  },
  {
    label: 'Inventario',
    path: '/panel/inventario',
    icon: 'archive',
    section: 'inventory',
    allowedRoles: ['administrador', 'recepcionista'],
  },
  {
    label: 'Mi centro',
    path: '/panel/mi-centro',
    icon: 'building',
    section: 'account',
    allowedRoles: ['administrador'],
  },
  {
    label: 'Subscripción',
    path: '/panel/subscripcion',
    icon: 'document',
    section: 'account',
    allowedRoles: ['administrador'],
  },
  {
    label: 'Mi perfil',
    path: '/panel/mi-perfil',
    icon: 'user',
    section: 'account',
    allowedRoles: [
      'administrador',
      'recepcionista',
      'rrhh',
      'diagnosticador',
      'dermo_esteticien',
      'fisioterapeuta',
      'manicurista',
      'cliente',
    ],
  },
];

const SECTIONS: { section: NavSection; label: string }[] = [
  { section: 'operations', label: 'Operativa' },
  { section: 'catalog', label: 'Catálogo' },
  { section: 'clinical', label: 'Clínico' },
  { section: 'team', label: 'Equipo' },
  { section: 'inventory', label: 'Inventario' },
  { section: 'account', label: 'Mi cuenta' },
];

interface NavGroup {
  section: NavSection;
  label: string;
  items: NavItem[];
}

@Component({
  selector: 'app-panel-sidebar',
  standalone: true,
  imports: [RouterLink, RouterLinkActive, IconComponent],
  templateUrl: './panel-sidebar.component.html',
})
export class PanelSidebarComponent {
  private readonly auth = inject(AuthService);
  protected readonly sidebar = inject(SidebarService);

  protected readonly groups = computed<NavGroup[]>(() => {
    const roles = this.auth.effectiveRoles();
    const isImpersonating = this.auth.isImpersonating();
    const visible = ALL_ITEMS.filter((item) => {
      if (isImpersonating && item.section === 'account') return false;
      return item.allowedRoles.some((role) => roles.includes(role));
    });
    return SECTIONS.map(({ section, label }) => ({
      section,
      label,
      items: visible.filter((item) => item.section === section),
    })).filter((group) => group.items.length > 0);
  });

  protected closeMobile(): void {
    this.sidebar.closeMobile();
  }
}
