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
    section: 'operativa',
    allowedRoles: ['administrador'],
  },
  {
    label: 'Cuadrante',
    path: '/panel/cuadrante',
    icon: 'calendar',
    section: 'operativa',
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
    label: 'Mapa del centro',
    path: '/panel/mapa',
    icon: 'map',
    section: 'operativa',
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
    label: 'Mis citas',
    path: '/panel/mis-citas',
    icon: 'calendar',
    section: 'operativa',
    allowedRoles: ['cliente'],
  },
  {
    label: 'Tratamientos',
    path: '/panel/tratamientos',
    icon: 'sparkles',
    section: 'catalogo',
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
    section: 'catalogo',
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
    section: 'clinico',
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
    section: 'clinico',
    allowedRoles: ['cliente'],
  },
  {
    label: 'Trabajadores',
    path: '/panel/trabajadores',
    icon: 'briefcase',
    section: 'equipo',
    allowedRoles: ['administrador', 'rrhh'],
  },
  {
    label: 'Horarios',
    path: '/panel/time-slots',
    icon: 'clock',
    section: 'equipo',
    allowedRoles: ['administrador', 'rrhh'],
  },
  {
    label: 'Productos',
    path: '/panel/productos',
    icon: 'package',
    section: 'inventario',
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
    section: 'inventario',
    allowedRoles: ['administrador', 'recepcionista'],
  },
  {
    label: 'Ventas',
    path: '/panel/ventas',
    icon: 'shopping-bag',
    section: 'inventario',
    allowedRoles: ['administrador', 'recepcionista'],
  },
  {
    label: 'Mi centro',
    path: '/panel/mi-centro',
    icon: 'building',
    section: 'cuenta',
    allowedRoles: ['administrador'],
  },
  {
    label: 'Mi perfil',
    path: '/panel/mi-perfil',
    icon: 'user',
    section: 'cuenta',
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
  { section: 'operativa', label: 'Operativa' },
  { section: 'catalogo', label: 'Catálogo' },
  { section: 'clinico', label: 'Clínico' },
  { section: 'equipo', label: 'Equipo' },
  { section: 'inventario', label: 'Inventario y ventas' },
  { section: 'cuenta', label: 'Mi cuenta' },
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
      if (isImpersonating && item.section === 'cuenta') return false;
      return item.allowedRoles.some((role) => roles.includes(role));
    });
    //Array de todas las secciones con sus respectivos tabs
    return SECTIONS.map(({ section, label }) => ({
      section,
      label,
      items: visible.filter((item) => item.section === section),
    })).filter((group) => group.items.length > 0);
    //filtro para quitar las secciones vacias ej: cliente entra y que le aparezca equipo pero en vacio
  });

  protected closeMobile(): void {
    this.sidebar.closeMobile();
  }
}
