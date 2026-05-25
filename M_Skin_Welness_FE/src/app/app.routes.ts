import { Routes } from '@angular/router';
import { authGuard, panelIndexRedirect, publicOnlyGuard } from './core/guards/auth.guard';
import { roleGuard } from './core/guards/role.guard';

const placeholder = () =>
  import('./features/panel/placeholder/placeholder.component').then((m) => m.PlaceholderComponent);

export const routes: Routes = [
  {
    path: '',
    pathMatch: 'full',
    redirectTo: 'login',
  },
  {
    path: 'login',
    canMatch: [publicOnlyGuard],
    loadComponent: () =>
      import('./features/auth/login/login.component').then((m) => m.LoginComponent),
  },
  {
    path: 'bienvenida',
    loadComponent: () =>
      import('./features/welcome/welcome.component').then((m) => m.WelcomeComponent),
  },
  {
    path: 'impersonate',
    loadComponent: () =>
      import('./features/impersonation/impersonation.component').then(
        (m) => m.ImpersonationComponent,
      ),
  },
  {
    path: 'panel',
    canMatch: [authGuard],
    loadComponent: () =>
      import('./layout/panel-layout/panel-layout.component').then((m) => m.PanelLayoutComponent),
    children: [
      { path: '', pathMatch: 'full', canActivate: [panelIndexRedirect], children: [] },
      {
        path: 'dashboard',
        canActivate: [roleGuard(['administrador', 'recepcionista', 'rrhh'])],
        data: { title: 'Dashboard' },
        loadComponent: placeholder,
      },
      {
        path: 'cuadrante',
        canActivate: [
          roleGuard([
            'administrador',
            'recepcionista',
            'rrhh',
            'diagnosticador',
            'dermo_esteticien',
            'fisioterapeuta',
            'manicurista',
          ]),
        ],
        data: { title: 'Cuadrante' },
        loadComponent: placeholder,
      },
      {
        path: 'mapa',
        canActivate: [
          roleGuard([
            'administrador',
            'recepcionista',
            'diagnosticador',
            'dermo_esteticien',
            'fisioterapeuta',
            'manicurista',
          ]),
        ],
        data: { title: 'Mapa del centro' },
        loadComponent: placeholder,
      },
      {
        path: 'mis-citas',
        canActivate: [roleGuard(['cliente'])],
        data: { title: 'Mis citas' },
        loadComponent: placeholder,
      },
      {
        path: 'clientes',
        canActivate: [
          roleGuard([
            'administrador',
            'recepcionista',
            'diagnosticador',
            'dermo_esteticien',
            'fisioterapeuta',
            'manicurista',
          ]),
        ],
        data: { title: 'Clientes' },
        loadComponent: placeholder,
      },
      {
        path: 'mi-consent',
        canActivate: [roleGuard(['cliente'])],
        data: { title: 'Mi consentimiento' },
        loadComponent: placeholder,
      },
      {
        path: 'trabajadores',
        canActivate: [roleGuard(['administrador', 'rrhh'])],
        data: { title: 'Trabajadores' },
        loadComponent: placeholder,
      },
      {
        path: 'time-slots',
        canActivate: [roleGuard(['administrador', 'rrhh'])],
        data: { title: 'Horarios' },
        loadComponent: placeholder,
      },
      {
        path: 'productos',
        canActivate: [
          roleGuard([
            'administrador',
            'recepcionista',
            'rrhh',
            'diagnosticador',
            'dermo_esteticien',
            'fisioterapeuta',
            'manicurista',
            'cliente',
          ]),
        ],
        data: { title: 'Productos' },
        loadComponent: placeholder,
      },
      {
        path: 'inventario',
        canActivate: [roleGuard(['administrador', 'recepcionista'])],
        data: { title: 'Inventario' },
        loadComponent: placeholder,
      },
      {
        path: 'ventas',
        canActivate: [roleGuard(['administrador', 'recepcionista'])],
        data: { title: 'Ventas' },
        loadComponent: placeholder,
      },
      {
        path: 'mi-centro',
        canActivate: [roleGuard(['administrador'])],
        data: { title: 'Mi centro' },
        loadComponent: placeholder,
      },
      {
        path: 'mi-perfil',
        canActivate: [
          roleGuard([
            'administrador',
            'recepcionista',
            'rrhh',
            'diagnosticador',
            'dermo_esteticien',
            'fisioterapeuta',
            'manicurista',
            'cliente',
          ]),
        ],
        data: { title: 'Mi perfil' },
        loadComponent: placeholder,
      },
    ],
  },
  {
    path: '**',
    redirectTo: 'login',
  },
];
