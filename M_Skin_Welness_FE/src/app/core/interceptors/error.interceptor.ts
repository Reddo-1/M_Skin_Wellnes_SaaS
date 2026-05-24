import { HttpErrorResponse, HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { Router } from '@angular/router';
import { tap } from 'rxjs/operators';
import { AuthService } from '../services/auth.service';

export const errorInterceptor: HttpInterceptorFn = (request, next) => {
  const auth = inject(AuthService);
  const router = inject(Router);

  return next(request).pipe(
    tap({
      error: (error: HttpErrorResponse) => {
        if (error.status === 401 && auth.isAuthenticated()) {
          auth.clearSession();
          router.navigate(['/login'], { queryParams: { sesionExpirada: '1' } });
        }
      },
    }),
  );
};
