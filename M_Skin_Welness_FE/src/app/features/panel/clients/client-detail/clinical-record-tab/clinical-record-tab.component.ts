import { DatePipe } from '@angular/common';
import { Component, computed, effect, inject, input, signal } from '@angular/core';
import { ClinicalRecordSummary } from '../../../../../core/models/clinical-record.model';
import { BodyType } from '../../../../../core/models/skin-evaluation.model';
import { User } from '../../../../../core/models/user.model';
import { AuthService } from '../../../../../core/services/auth.service';
import { ClinicalRecordService } from '../../../../../core/services/clinical-record.service';
import { ConsentService, TreatmentConsentSummary, UpdateAptitudeData } from '../../../../../core/services/consent.service';
import { NotificationService } from '../../../../../core/services/notification.service';
import { apiError, loadResourceError } from '../../../../../core/utils/form.util';
import { AlertComponent } from '../../../../../shared/ui/alert/alert.component';
import { SegmentedControlComponent, SegmentedControlOption } from '../../../../../shared/ui/segmented-control/segmented-control.component';
import { ClinicalImagesComponent } from '../clinical-images/clinical-images.component';
import { AptitudeModalComponent } from './modals/aptitude-modal/aptitude-modal.component';
import { ClinicalRecordFormValue, ClinicalRecordModalComponent } from './modals/clinical-record-modal/clinical-record-modal.component';

const BODY_TYPE_OPTIONS: SegmentedControlOption<BodyType>[] = [
  { value: 'facial', label: 'Facial' },
  { value: 'corporal', label: 'Corporal' },
];

@Component({
  selector: 'app-clinical-record-tab',
  standalone: true,
  imports: [DatePipe, AlertComponent, ClinicalImagesComponent, ClinicalRecordModalComponent, AptitudeModalComponent, SegmentedControlComponent],
  templateUrl: './clinical-record-tab.component.html',
})
export class ClinicalRecordTabComponent {
  readonly client = input.required<User>();

  private readonly records = inject(ClinicalRecordService);
  private readonly consents = inject(ConsentService);
  private readonly notifications = inject(NotificationService);
  protected readonly auth = inject(AuthService);

  protected readonly bodyTypeOptions = BODY_TYPE_OPTIONS;

  protected readonly items = signal<ClinicalRecordSummary[]>([]);
  protected readonly loading = signal(false);
  protected readonly errorMessage = signal<string | null>(null);
  protected readonly selectedBodyType = signal<BodyType>('facial');

  //el consentimiento firmado es prerrequisito para crear la ficha
  protected readonly consentSigned = signal(false);
  protected readonly treatmentConsents = signal<TreatmentConsentSummary[]>([]);

  protected readonly modalOpen = signal(false);
  protected readonly recordToEdit = signal<ClinicalRecordSummary | null>(null);
  protected readonly submitting = signal(false);

  protected readonly aptitudeModalOpen = signal(false);
  protected readonly treatmentConsentToEdit = signal<TreatmentConsentSummary | null>(null);
  protected readonly aptitudeSubmitting = signal(false);

  protected readonly currentRecord = computed(() => {
    const target = this.selectedBodyType();
    return this.items().find((record) => record.body_type === target) ?? null;
  });

  //solo el rol clínico fija la aptitud; el superadmin impersonando queda fuera (su center_id es null)
  protected readonly canEditAptitude = computed(() => {
    if (this.auth.isImpersonating() && this.auth.hasRole('superadmin')) {
      return false;
    }
    return this.auth.hasPermission('treatment_consents.update');
  });

  constructor() {
    effect(() => {
      const userId = this.client().id;
      void this.load(userId);
    });
  }

  protected selectBodyType(value: BodyType): void {
    this.selectedBodyType.set(value);
  }

  protected openCreate(): void {
    this.recordToEdit.set(null);
    this.modalOpen.set(true);
  }

  protected openEdit(): void {
    const record = this.currentRecord();
    if (record !== null) {
      this.recordToEdit.set(record);
      this.modalOpen.set(true);
    }
  }

  protected closeModal(): void {
    if (this.submitting()) return;
    this.modalOpen.set(false);
  }

  protected async submit(value: ClinicalRecordFormValue): Promise<void> {
    const record = this.recordToEdit();
    const generalNotes = value.general_notes.trim() === '' ? null : value.general_notes;

    this.submitting.set(true);
    try {
      if (record !== null) {
        const updated = await this.records.update(record.id, { general_notes: generalNotes });
        this.items.update((current) =>
          current.map((item) => (item.id === updated.id ? updated : item)),
        );
        this.notifications.toast.success('Ficha actualizada.');
      } else {
        const evaluation = value.evaluation;
        if (evaluation === null) return;
        const bodyType = this.selectedBodyType();
        const created = await this.records.create({
          user_id: this.client().id,
          body_type: bodyType,
          general_notes: generalNotes,
          evaluation: {
            skin_type_id: evaluation.skin_type_id,
            evaluation_date: evaluation.evaluation_date,
            general_notes: evaluation.general_notes.trim() === '' ? null : evaluation.general_notes,
            variation_ids: evaluation.variation_ids,
          },
        });
        this.items.update((current) => [...current, created]);
        this.notifications.toast.success(`Ficha ${bodyType} creada.`);
      }
      this.modalOpen.set(false);
    } catch (error) {
      this.notifications.toast.error(apiError(error));
    } finally {
      this.submitting.set(false);
    }
  }

  protected openAptitudeEditor(treatmentConsent: TreatmentConsentSummary): void {
    this.treatmentConsentToEdit.set(treatmentConsent);
    this.aptitudeModalOpen.set(true);
  }

  protected closeAptitudeModal(): void {
    if (this.aptitudeSubmitting()) return;
    this.aptitudeModalOpen.set(false);
  }

  protected async submitAptitude(value: UpdateAptitudeData): Promise<void> {
    const target = this.treatmentConsentToEdit();
    if (target === null) return;

    this.aptitudeSubmitting.set(true);
    try {
      const updated = await this.consents.updateAptitude(target.id, value);
      this.treatmentConsents.update((current) =>
        current.map((tc) => (tc.id === updated.id ? updated : tc)),
      );
      this.notifications.toast.success('Aptitud actualizada.');
      this.aptitudeModalOpen.set(false);
    } catch (error) {
      this.notifications.toast.error(apiError(error));
    } finally {
      this.aptitudeSubmitting.set(false);
    }
  }

  private async load(userId: number): Promise<void> {
    this.loading.set(true);
    this.errorMessage.set(null);
    try {
      const [records, active] = await Promise.all([
        this.records.listByUser(userId),
        this.consents.activeConsentsFor(userId),
      ]);
      this.items.set(records);
      this.consentSigned.set(active.client !== null);
      this.treatmentConsents.set(active.treatments);
    } catch {
      const message = loadResourceError('la ficha clínica del cliente');
      this.errorMessage.set(message);
    } finally {
      this.loading.set(false);
    }
  }
}
