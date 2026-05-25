import { Component, OnInit, inject, input } from '@angular/core';
import { Router } from '@angular/router';
import { AuthService } from '../../core/services/auth.service';
import { NotificationService } from '../../core/services/notification.service';

@Component({
  selector: 'app-impersonation',
  standalone: true,
  templateUrl: './impersonation.component.html',
})
export class ImpersonationComponent implements OnInit {
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);
  private readonly notifications = inject(NotificationService);

  readonly token = input<string | undefined>();
  readonly center_id = input<string | undefined>();

  async ngOnInit(): Promise<void> {
    const token = this.token();
    const rawCenterId = this.center_id();
    const centerId = rawCenterId !== undefined ? Number(rawCenterId) : NaN;

    if (!token || !Number.isInteger(centerId) || centerId <= 0) {
      this.notifications.toast.error('El enlace de impersonación no es válido.');
      this.router.navigateByUrl('/login');
      return;
    }

    this.auth.startImpersonation(token, centerId);

    try {
      const user = await this.auth.fetchMe();
      this.router.navigateByUrl(this.auth.defaultRouteForRoles(user.roles));
    } catch {
      this.auth.clearSession();
      this.notifications.toast.error(
        'No se pudo iniciar la sesión en el centro. Vuelve al panel del superadmin y prueba otra vez.',
      );
      this.router.navigateByUrl('/login');
    }
  }
}
