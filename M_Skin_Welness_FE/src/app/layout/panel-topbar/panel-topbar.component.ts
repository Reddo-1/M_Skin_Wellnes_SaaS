import { Component, computed, effect, inject, signal } from '@angular/core';
import { Router, RouterLink } from '@angular/router';
import { AuthService } from '../../core/services/auth.service';
import { SidebarService } from '../../core/services/sidebar.service';
import { ROLE_LABELS } from '../../../environments/environment';
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
    const primary = this.auth.user()?.roles[0];
    if (!primary) return '';
    return ROLE_LABELS.find((entry) => entry.code === primary)?.label ?? primary;
  });

  protected readonly impersonationCenterName = computed(() => {
    if (!this.auth.isImpersonating()) return '';
    return this.auth.user()?.center?.name ?? `Centro #${this.auth.impersonationCenterId()}`;
  });

  protected readonly dropdownOpen = signal(false);
  protected readonly loadingMessage = signal<string | null>(null);

  constructor() {
    effect((onCleanup) => {
      if (!this.dropdownOpen()) return;
      const close = () => this.dropdownOpen.set(false);
      const closeOnEscape = (event: KeyboardEvent) => {
        if (event.key === 'Escape') this.dropdownOpen.set(false);
      };
      document.addEventListener('click', close);
      document.addEventListener('keydown', closeOnEscape);
      onCleanup(() => {
        document.removeEventListener('click', close);
        document.removeEventListener('keydown', closeOnEscape);
      });
    });
  }

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

