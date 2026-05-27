import { DatePipe } from '@angular/common';
import { Component, computed, effect, inject, input, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { AuthService } from '../../../../core/services/auth.service';
import { ClientService } from '../../../../core/services/client.service';
import { ConsentService, ClientConsentSummary, TreatmentConsentSummary } from '../../../../core/services/consent.service';
import { NotificationService } from '../../../../core/services/notification.service';
import { User } from '../../../../core/models/user.model';
import { loadResourceError } from '../../../../core/utils/form.util';
import { AlertComponent } from '../../../../shared/ui/alert/alert.component';
import { IconComponent } from '../../../../shared/ui/icon/icon.component';
import { ClinicalRecordTabComponent } from './clinical-record-tab/clinical-record-tab.component';

type DetailTab = 'personal' | 'ficha' | 'evaluaciones' | 'consentimientos' | 'archivos' | 'citas' | 'sesiones';

const TABS: { key: DetailTab; label: string }[] = [
  { key: 'personal', label: 'Datos personales' },
  { key: 'ficha', label: 'Ficha clínica' },
  { key: 'evaluaciones', label: 'Evaluaciones de piel' },
  { key: 'consentimientos', label: 'Consentimientos' },
  { key: 'archivos', label: 'Archivos' },
  { key: 'citas', label: 'Citas' },
  { key: 'sesiones', label: 'Sesiones' },
];

@Component({
  selector: 'app-client-detail',
  standalone: true,
  imports: [DatePipe, RouterLink, AlertComponent, IconComponent, ClinicalRecordTabComponent],
  templateUrl: './client-detail.component.html',
})
export class ClientDetailComponent {
  readonly id = input.required<string>();

  private readonly clients = inject(ClientService);
  private readonly consents = inject(ConsentService);
  protected readonly auth = inject(AuthService);
  private readonly notifications = inject(NotificationService);

  protected readonly tabs = TABS;

  protected readonly client = signal<User | null>(null);
  protected readonly loading = signal(false);
  protected readonly errorMessage = signal<string | null>(null);
  protected readonly activeTab = signal<DetailTab>('personal');

  protected readonly activeConsent = signal<ClientConsentSummary | null>(null);
  protected readonly activeTreatmentConsents = signal<TreatmentConsentSummary[]>([]);
  protected readonly consentsLoading = signal(false);
  protected readonly consentsError = signal<string | null>(null);
  private readonly consentsLoaded = signal(false);

  //el superadmin impersonando no firma consents: su center_id es null y el reviewer del consent quedaria huerfano
  protected readonly canSignConsent = computed(() => {
    if (this.auth.isImpersonating() && this.auth.hasRole('superadmin')) {
      return false;
    }
    return this.auth.hasPermission('client_consents.create')
      && this.auth.hasPermission('treatment_consents.create');
  });

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
    if (tab === 'consentimientos' && !this.consentsLoaded()) {
      void this.loadConsents();
    }
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

  async loadConsents(): Promise<void> {
    const userId = Number(this.id());
    if (!Number.isInteger(userId) || userId <= 0) return;

    this.consentsLoading.set(true);
    this.consentsError.set(null);
    try {
      const active = await this.consents.activeConsentsFor(userId);
      this.activeConsent.set(active.client);
      this.activeTreatmentConsents.set(active.treatments);
      this.consentsLoaded.set(true);
    } catch {
      this.consentsError.set('No se ha podido cargar el consentimiento del cliente.');
    } finally {
      this.consentsLoading.set(false);
    }
  }
}
