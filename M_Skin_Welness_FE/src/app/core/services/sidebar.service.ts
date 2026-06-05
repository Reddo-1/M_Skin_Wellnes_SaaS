import { Injectable, signal } from '@angular/core';

@Injectable({ providedIn: 'root' })
export class SidebarService {
  readonly isDesktopCollapsed = signal(false);

  readonly isMobileOpen = signal(false);

  toggleDesktop(): void {
    this.isDesktopCollapsed.update((value) => !value);
  }

  toggleMobile(): void {
    this.isMobileOpen.update((value) => !value);
  }

  closeMobile(): void {
    this.isMobileOpen.set(false);
  }
}
