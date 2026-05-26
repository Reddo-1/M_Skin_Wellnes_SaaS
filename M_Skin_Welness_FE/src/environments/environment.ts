import { UserRole } from '../app/core/models/user.model';

export const environment = {
  production: false,
  apiUrl: 'http://localhost:8000/api',
};

export const ROLE_LABELS: { code: UserRole; label: string }[] = [
  { code: 'superadmin', label: 'Superadmin' },
  { code: 'administrador', label: 'Administrador' },
  { code: 'recepcionista', label: 'Recepcionista' },
  { code: 'rrhh', label: 'RRHH' },
  { code: 'diagnosticador', label: 'Diagnosticador' },
  { code: 'dermo_esteticien', label: 'Dermoesteticien' },
  { code: 'fisioterapeuta', label: 'Fisioterapeuta' },
  { code: 'manicurista', label: 'Manicurista' },
  { code: 'cliente', label: 'Cliente' },
];
