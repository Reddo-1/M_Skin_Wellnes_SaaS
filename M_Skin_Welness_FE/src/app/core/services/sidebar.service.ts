import { Injectable, signal } from '@angular/core';

@Injectable({ providedIn: 'root' })
export class SidebarService {
  //para abrir o cerrar en escritorio el panel lateral
  readonly isDesktopCollapsed = signal(false);

  readonly isMobileOpen = signal(false);

  toggleDesktop(): void {
    this.isDesktopCollapsed.update((value) => !value);
  }

  toggleMobile(): void {
    this.isMobileOpen.update((value) => !value);
  }

  //se cierra si o si ej de uso: pinchar fuera y/o seleccionar un apartado (xolo en movil)
  closeMobile(): void {
    this.isMobileOpen.set(false);
  }
}
