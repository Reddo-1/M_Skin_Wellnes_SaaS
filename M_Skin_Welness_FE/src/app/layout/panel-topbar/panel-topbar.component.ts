import { Component, HostListener, computed, inject, signal } from '@angular/core';
import { Router, RouterLink } from '@angular/router';
import { AuthService } from '../../core/services/auth.service';
import { SidebarService } from '../../core/services/sidebar.service';
import { IconComponent } from '../../shared/ui/icon/icon.component';
import { LoadingOverlayComponent } from '../../shared/ui/loading-overlay/loading-overlay.component';

@Component({
  selector: 'app-panel-topbar',
  standalone: true,
  imports: [RouterLink, IconComponent, LoadingOverlayComponent],
  templateUrl: './panel-topbar.component.html',
})
export class PanelTopbarComponent {
  protected readonly auth = inject(AuthService);
  protected readonly sidebar = inject(SidebarService);
  private readonly router = inject(Router);

  protected readonly primaryRoleLabel = computed(() => {
    const roles = this.auth.user()?.roles ?? [];
    if (roles.length === 0) return '';
    return ROLE_LABELS[roles[0]] ?? roles[0];
  });

  protected readonly impersonationCenterName = computed(() => {
    if (!this.auth.isImpersonating()) return '';
    return this.auth.user()?.center?.name ?? `Centro #${this.auth.impersonationCenterId()}`;
  });

  protected readonly dropdownOpen = signal(false);
  protected readonly loadingMessage = signal<string | null>(null);

  protected toggleSidebar(): void {
    if (window.innerWidth >= 1280) {
      this.sidebar.toggleDesktop();
    } else {
      this.sidebar.toggleMobile();
    }
  }

  protected toggleDropdown(event: MouseEvent): void {
    event.stopPropagation();
    this.dropdownOpen.update((value) => !value);
  }

  protected closeDropdown(): void {
    this.dropdownOpen.set(false);
  }

  @HostListener('document:click')
  protected onDocumentClick(): void {
    if (this.dropdownOpen()) {
      this.dropdownOpen.set(false);
    }
  }

  @HostListener('document:keydown.escape')
  protected onEscape(): void {
    if (this.dropdownOpen()) {
      this.dropdownOpen.set(false);
    }
  }

  async logout(): Promise<void> {
    this.dropdownOpen.set(false);
    this.loadingMessage.set('Cerrando sesión…');
    try {
      await this.auth.logout();
    } finally {
      this.router.navigate(['/login']);
    }
  }

  async exitImpersonation(): Promise<void> {
    const centerId = this.auth.impersonationCenterId();
    this.loadingMessage.set('Saliendo del centro…');
    try {
      await this.auth.logout();
    } finally {
      window.location.href = centerId !== null ? `/admin/centers/${centerId}` : '/admin/centers';
    }
  }
}

const ROLE_LABELS: Record<string, string> = {
  superadmin: 'Superadmin',
  administrador: 'Administrador',
  recepcionista: 'Recepcionista',
  rrhh: 'RRHH',
  diagnosticador: 'Diagnosticador',
  dermo_esteticien: 'Dermoesteticien',
  fisioterapeuta: 'Fisioterapeuta',
  manicurista: 'Manicurista',
  cliente: 'Cliente',
};
