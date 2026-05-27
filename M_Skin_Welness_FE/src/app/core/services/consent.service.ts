import { Injectable, inject } from '@angular/core';
import { TreatmentSummary } from '../models/treatment.model';
import { ApiService } from './api.service';

export interface ClientConsentSummary {
  id: number;
  user_id: number;
  reviewed_by_user_id: number;
  clinical_photos_consent: boolean;
  marketing_data_consent: boolean;
  commercial_images_consent: boolean;
  signature_user_file_id: number | null;
  signature_url: string | null;
  pdf_user_file_id: number | null;
  pdf_url: string | null;
  signed_at: string | null;
  notes: string | null;
  is_active: boolean;
  reviewer?: { id: number; name: string };
  created_at: string | null;
}

export interface TreatmentConsentSummary {
  id: number;
  user_id: number;
  treatment_id: number;
  reviewed_by_user_id: number;
  review_date: string | null;
  is_suitable: boolean;
  unsuitability_reason: string | null;
  treatment_consent: boolean;
  notes: string | null;
  is_active: boolean;
  treatment?: { id: number; name: string };
  reviewer?: { id: number; name: string };
}

export interface ConsentWizardPayload {
  user_id: number;
  rgpd: {
    clinical_photos_consent: boolean;
    marketing_data_consent: boolean;
    commercial_images_consent: boolean;
  };
  treatments: {
    treatment_id: number;
    is_suitable: boolean;
    unsuitability_reason: string | null;
    treatment_consent: boolean;
    notes: string | null;
  }[];
  signature_base64: string;
  notes: string | null;
}

export interface SignedConsent {
  id: number;
  user_id: number;
  signed_at: string | null;
}

@Injectable({ providedIn: 'root' })
export class ConsentService {
  private readonly api = inject(ApiService);

  async listActiveTreatments(): Promise<TreatmentSummary[]> {
    const page = await this.api.getCollection<TreatmentSummary>('/treatments', {
      is_active: true,
    });
    return page.data;
  }

  async submitWizard(payload: ConsentWizardPayload): Promise<SignedConsent> {
    return await this.api.postResource<SignedConsent>('/consents/wizard', payload);
  }

  async activeConsentsFor(userId: number): Promise<ActiveConsents> {
    const params = { user_id: userId, only_active: true };
    const [clientPage, treatmentPage] = await Promise.all([
      this.api.getCollection<ClientConsentSummary>('/client-consents', params),
      this.api.getCollection<TreatmentConsentSummary>('/treatment-consents', params),
    ]);
    return {
      client: clientPage.data[0] ?? null,
      treatments: treatmentPage.data,
    };
  }
}

export interface ActiveConsents {
  client: ClientConsentSummary | null;
  treatments: TreatmentConsentSummary[];
}
