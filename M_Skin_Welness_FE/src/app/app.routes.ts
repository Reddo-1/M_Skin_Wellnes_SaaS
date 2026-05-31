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
    path: 'recuperar-password',
    canMatch: [publicOnlyGuard],
    loadComponent: () =>
      import('./features/auth/forgot-password/forgot-password.component').then(
        (m) => m.ForgotPasswordComponent,
      ),
  },
  {
    path: 'reset-password',
    canMatch: [publicOnlyGuard],
    loadComponent: () =>
      import('./features/auth/reset-password/reset-password.component').then(
        (m) => m.ResetPasswordComponent,
      ),
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
      //redirección a su ruta correspondiente dependiendo del rol.
      { path: '', pathMatch: 'full', canActivate: [panelIndexRedirect], children: [] },
      {
        path: 'dashboard',
        canActivate: [roleGuard(['administrador'])],
        data: { title: 'Dashboard' },
        loadComponent: () =>
          import('./features/panel/dashboard/dashboard.component').then(
            (m) => m.DashboardComponent,
          ),
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
        loadComponent: () =>
          import('./features/panel/cuadrante/cuadrante.component').then((m) => m.CuadranteComponent),
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
        path: 'tratamientos',
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
        data: { title: 'Tratamientos' },
        loadComponent: () =>
          import('./features/panel/treatments/treatments-list.component').then(
            (m) => m.TreatmentsListComponent,
          ),
      },
      {
        path: 'maquinas',
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
        data: { title: 'Máquinas' },
        loadComponent: () =>
          import('./features/panel/machines/machines-list.component').then(
            (m) => m.MachinesListComponent,
          ),
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
        loadComponent: () =>
          import('./features/panel/clients/clients-list.component').then(
            (m) => m.ClientsListComponent,
          ),
      },
      {
        path: 'clientes/:id',
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
        data: { title: 'Ficha del cliente' },
        loadComponent: () =>
          import('./features/panel/clients/client-detail/client-detail.component').then(
            (m) => m.ClientDetailComponent,
          ),
      },
      {
        path: 'clientes/:id/consent/nuevo',
        canActivate: [roleGuard(['recepcionista', 'diagnosticador', 'administrador'])],
        data: { title: 'Firmar consentimiento' },
        loadComponent: () =>
          import('./features/panel/clients/consent-wizard/consent-wizard.component').then(
            (m) => m.ConsentWizardComponent,
          ),
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
        loadComponent: () =>
          import('./features/panel/workers/workers-list.component').then(
            (m) => m.WorkersListComponent,
          ),
      },
      {
        path: 'trabajadores/:id',
        canActivate: [roleGuard(['administrador', 'rrhh'])],
        data: { title: 'Ficha del trabajador' },
        loadComponent: () =>
          import('./features/panel/workers/worker-detail/worker-detail.component').then(
            (m) => m.WorkerDetailComponent,
          ),
      },
      {
        path: 'time-slots',
        canActivate: [roleGuard(['administrador', 'rrhh'])],
        data: { title: 'Franjas horarias' },
        loadComponent: () =>
          import('./features/panel/time-slots/time-slots-list.component').then(
            (m) => m.TimeSlotsListComponent,
          ),
      },
      {
        path: 'productos',
        canActivate: [
          roleGuard([
            'administrador',
            'recepcionista',
            'diagnosticador',
            'dermo_esteticien',
            'fisioterapeuta',
            'manicurista',
            'cliente',
          ]),
        ],
        data: { title: 'Productos' },
        loadComponent: () =>
          import('./features/panel/products/products-list.component').then(
            (m) => m.ProductsListComponent,
          ),
      },
      {
        path: 'productos/:id',
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
        data: { title: 'Detalle del producto' },
        loadComponent: () =>
          import('./features/panel/products/product-detail/product-detail.component').then(
            (m) => m.ProductDetailComponent,
          ),
      },
      {
        path: 'inventario',
        canActivate: [roleGuard(['administrador', 'recepcionista'])],
        data: { title: 'Inventario' },
        loadComponent: () =>
          import('./features/panel/inventory/inventory.component').then(
            (m) => m.InventoryComponent,
          ),
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
        loadComponent: () =>
          import('./features/panel/profile/profile.component').then((m) => m.ProfileComponent),
      },
    ],
  },
  {
    path: '**',
    redirectTo: 'login',
  },
];
