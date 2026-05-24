import { Component, HostListener, computed, inject, signal } from '@angular/core';
import { Router, RouterLink } from '@angular/router';
import { AuthService } from '../../core/services/auth.service';
import { SidebarService } from '../../core/services/sidebar.service';

@Component({
  selector: 'app-panel-topbar',
  standalone: true,
  imports: [RouterLink],
  templateUrl: './panel-topbar.component.html',
})
export class PanelTopbarComponent {
  private readonly auth = inject(AuthService);
  private readonly sidebar = inject(SidebarService);
  private readonly router = inject(Router);

  protected readonly user = this.auth.user;
  protected readonly isImpersonating = this.auth.isImpersonating;
  protected readonly impersonationCenterId = this.auth.impersonationCenterId;

  protected readonly initials = computed(() => {
    const name = this.user()?.name ?? '';
    const parts = name.trim().split(/\s+/).filter(Boolean);
    if (parts.length === 0) return '?';
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
  });

  protected readonly primaryRoleLabel = computed(() => {
    const roles = this.user()?.roles ?? [];
    if (roles.length === 0) return '';
    return ROLE_LABELS[roles[0]] ?? roles[0];
  });

  protected readonly impersonationCenterName = computed(() => {
    if (!this.isImpersonating()) return '';
    return this.user()?.center?.name ?? `Centro #${this.impersonationCenterId()}`;
  });

  protected readonly dropdownOpen = signal(false);

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
    try {
      await this.auth.logout();
    } finally {
      this.router.navigate(['/login']);
    }
  }

  async exitImpersonation(): Promise<void> {
    const centerId = this.impersonationCenterId();
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
