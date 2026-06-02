import { Component, effect, inject, input, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { ClientService } from '../../../../core/services/client.service';
import { NotificationService } from '../../../../core/services/notification.service';
import { User } from '../../../../core/models/user.model';
import { loadResourceError } from '../../../../core/utils/form.util';
import { AlertComponent } from '../../../../shared/ui/alert/alert.component';
import { IconComponent } from '../../../../shared/ui/icon/icon.component';
import { ClinicalRecordTabComponent } from './clinical-record-tab/clinical-record-tab.component';
import { ConsentsTabComponent } from './consents-tab/consents-tab.component';
import { PersonalTabComponent } from './personal-tab/personal-tab.component';
import { SessionsTabComponent } from './sessions-tab/sessions-tab.component';
import { SkinEvaluationsTabComponent } from './skin-evaluations-tab/skin-evaluations-tab.component';

type DetailTab = 'personal' | 'ficha' | 'evaluaciones' | 'consentimientos' | 'sesiones';

const TABS: { key: DetailTab; label: string }[] = [
  { key: 'personal', label: 'Datos personales' },
  { key: 'ficha', label: 'Ficha clínica' },
  { key: 'evaluaciones', label: 'Evaluaciones de piel' },
  { key: 'consentimientos', label: 'Consentimientos' },
  { key: 'sesiones', label: 'Sesiones' },
];

@Component({
  selector: 'app-client-detail',
  standalone: true,
  imports: [
    RouterLink,
    AlertComponent,
    IconComponent,
    PersonalTabComponent,
    ClinicalRecordTabComponent,
    SkinEvaluationsTabComponent,
    ConsentsTabComponent,
    SessionsTabComponent,
  ],
  templateUrl: './client-detail.component.html',
})
export class ClientDetailComponent {
  readonly id = input.required<string>();

  private readonly clients = inject(ClientService);
  private readonly notifications = inject(NotificationService);

  protected readonly tabs = TABS;

  protected readonly client = signal<User | null>(null);
  protected readonly loading = signal(false);
  protected readonly errorMessage = signal<string | null>(null);
  protected readonly activeTab = signal<DetailTab>('personal');

  constructor() {
    effect(() => {
      const raw = this.id();
      const userId = Number(raw);
      if (!Number.isInteger(userId) || userId <= 0) {
        this.errorMessage.set('El identificador del cliente no es válido.');
        return;
      }
      void this.load(userId);
    });
  }

  protected setActiveTab(tab: DetailTab): void {
    this.activeTab.set(tab);
  }

  private async load(userId: number): Promise<void> {
    this.loading.set(true);
    this.errorMessage.set(null);
    try {
      const client = await this.clients.getById(userId);
      this.client.set(client);
    } catch {
      const message = loadResourceError('la ficha del cliente');
      this.errorMessage.set(message);
      this.notifications.toast.error(message);
    } finally {
      this.loading.set(false);
    }
  }
}
