import { DatePipe } from '@angular/common';
import { Component, computed, effect, inject, input, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { User } from '../../../../../core/models/user.model';
import { AuthService } from '../../../../../core/services/auth.service';
import {
  ClientConsentSummary,
  ConsentService,
  TreatmentConsentSummary,
} from '../../../../../core/services/consent.service';
import { AlertComponent } from '../../../../../shared/ui/alert/alert.component';

@Component({
  selector: 'app-consents-tab',
  standalone: true,
  imports: [DatePipe, RouterLink, AlertComponent],
  templateUrl: './consents-tab.component.html',
})
export class ConsentsTabComponent {
  readonly client = input.required<User>();

  private readonly consents = inject(ConsentService);
  protected readonly auth = inject(AuthService);

  protected readonly activeConsent = signal<ClientConsentSummary | null>(null);
  protected readonly activeTreatmentConsents = signal<TreatmentConsentSummary[]>([]);
  protected readonly loading = signal(false);
  protected readonly errorMessage = signal<string | null>(null);

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
      const userId = this.client().id;
      void this.load(userId);
    });
  }

  private async load(userId: number): Promise<void> {
    this.loading.set(true);
    this.errorMessage.set(null);
    try {
      const active = await this.consents.activeConsentsFor(userId);
      this.activeConsent.set(active.client);
      this.activeTreatmentConsents.set(active.treatments);
    } catch {
      this.errorMessage.set('No se ha podido cargar el consentimiento del cliente.');
    } finally {
      this.loading.set(false);
    }
  }
}
