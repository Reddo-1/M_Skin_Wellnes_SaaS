import { HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { AuthService } from '../services/auth.service';

export const authInterceptor: HttpInterceptorFn = (request, next) => {
  const auth = inject(AuthService);
  const token = auth.token();

  let prepared = request;

  if (token !== null) {
    prepared = prepared.clone({
      setHeaders: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
      },
    });
  } else {
    prepared = prepared.clone({
      setHeaders: { Accept: 'application/json' },
    });
  }

  const impersonationCenterId = auth.impersonationCenterId();
  if (
    impersonationCenterId !== null &&
    auth.hasRole('superadmin') &&
    !prepared.params.has('center_id')
  ) {
    prepared = prepared.clone({
      setParams: { center_id: String(impersonationCenterId) },
    });
  }

  return next(prepared);
};
