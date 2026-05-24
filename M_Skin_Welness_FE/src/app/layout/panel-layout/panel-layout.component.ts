import { Component, inject } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { SidebarService } from '../../core/services/sidebar.service';
import { PanelSidebarComponent } from '../panel-sidebar/panel-sidebar.component';
import { PanelTopbarComponent } from '../panel-topbar/panel-topbar.component';

@Component({
  selector: 'app-panel-layout',
  standalone: true,
  imports: [RouterOutlet, PanelSidebarComponent, PanelTopbarComponent],
  templateUrl: './panel-layout.component.html',
})
export class PanelLayoutComponent {
  protected readonly sidebar = inject(SidebarService);
}
