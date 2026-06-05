import { DatePipe } from '@angular/common';
import { Component, computed, inject, signal } from '@angular/core';
import { AuthService } from '../../../core/services/auth.service';
import { ActiveConsents, ConsentService } from '../../../core/services/consent.service';
import { loadResourceError } from '../../../core/utils/form.util';
import { AlertComponent } from '../../../shared/ui/alert/alert.component';

@Component({
  selector: 'app-my-consent',
  standalone: true,
  imports: [DatePipe, AlertComponent],
  templateUrl: './my-consent.component.html',
})
export class MyConsentComponent {
  private readonly consents = inject(ConsentService);
  private readonly auth = inject(AuthService);

  protected readonly data = signal<ActiveConsents | null>(null);
  protected readonly loading = signal(false);
  protected readonly errorMessage = signal<string | null>(null);

  protected readonly consent = computed(() => this.data()?.client ?? null);
  protected readonly treatments = computed(() => this.data()?.treatments ?? []);

  constructor() {
    void this.load();
  }

  private async load(): Promise<void> {
    const userId = this.auth.user()?.id;
    if (userId === undefined) {
      this.errorMessage.set(loadResourceError('tu consentimiento'));
      return;
    }

    this.loading.set(true);
    this.errorMessage.set(null);
    try {
      this.data.set(await this.consents.activeConsentsFor(userId));
    } catch {
      this.errorMessage.set(loadResourceError('tu consentimiento'));
    } finally {
      this.loading.set(false);
    }
  }
}
