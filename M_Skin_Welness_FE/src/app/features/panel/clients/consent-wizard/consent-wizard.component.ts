import { CurrencyPipe } from '@angular/common';
import { Component, effect, inject, input, signal, viewChild } from '@angular/core';
import { FormArray, FormBuilder, FormControl, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { SignaturePadComponent } from '@almothafar/angular-signature-pad';
import { ClientService } from '../../../../core/services/client.service';
import { ConsentService } from '../../../../core/services/consent.service';
import { NotificationService } from '../../../../core/services/notification.service';
import { User } from '../../../../core/models/user.model';
import { TreatmentSummary } from '../../../../core/models/treatment.model';
import { HttpErrorResponse } from '@angular/common/http';
import { AlertComponent } from '../../../../shared/ui/alert/alert.component';
import { BooleanPipe } from '../../../../shared/pipes/boolean.pipe';

interface TreatmentFormControls {
  treatment_id: FormControl<number>;
  is_suitable: FormControl<boolean | null>;
  unsuitability_reason: FormControl<string>;
  treatment_consent: FormControl<boolean | null>;
  notes: FormControl<string>;
}

@Component({
  selector: 'app-consent-wizard',
  standalone: true,
  imports: [CurrencyPipe, ReactiveFormsModule, RouterLink, SignaturePadComponent, AlertComponent, BooleanPipe],
  templateUrl: './consent-wizard.component.html',
})
export class ConsentWizardComponent {
  readonly id = input.required<string>();

  private readonly fb = inject(FormBuilder);
  private readonly clients = inject(ClientService);
  private readonly consents = inject(ConsentService);
  private readonly notifications = inject(NotificationService);
  private readonly router = inject(Router);

  //Como SignaturePad se pone como componente y hay que coger el canvas que crea por debajo tenemos que hacer esto.
  private readonly signaturePad = viewChild<SignaturePadComponent>('signaturePad');

  protected readonly client = signal<User | null>(null);
  protected readonly treatments = signal<TreatmentSummary[]>([]);
  protected readonly loading = signal(false);
  protected readonly submitting = signal(false);
  protected readonly currentStep = signal<1 | 2>(1);
  protected readonly loadError = signal<string | null>(null);
  protected readonly generalError = signal<string | null>(null);
  protected readonly signatureMissing = signal(false);

  protected readonly signaturePadOptions = {
    minWidth: 1,
    maxWidth: 2.5,
    penColor: '#1f2937',
    backgroundColor: 'rgb(255, 255, 255)',
    canvasWidth: 800,
    canvasHeight: 220,
  };

  protected readonly form = this.fb.nonNullable.group({
    rgpd: this.fb.nonNullable.group({
      clinical_photos_consent: this.fb.nonNullable.control<boolean | null>(null, Validators.required),
      marketing_data_consent: this.fb.nonNullable.control<boolean | null>(null, Validators.required),
      commercial_images_consent: this.fb.nonNullable.control<boolean | null>(null, Validators.required),
    }),
    treatments: this.fb.nonNullable.array<FormGroup<TreatmentFormControls>>([]),
    notes: this.fb.nonNullable.control<string>(''),
  });

  constructor() {
    effect(async () => {
      const raw = this.id();
      const userId = Number(raw);
      if (!Number.isInteger(userId) || userId <= 0) {
        this.loadError.set('El identificador del cliente no es válido.');
        return;
      }
      await this.load(userId);
    });
  }

  protected get treatmentsArray(): FormArray<FormGroup<TreatmentFormControls>> {
    return this.form.controls.treatments;
  }

  protected goToStep(step: 1 | 2): void {
    if (step === 2 && !this.validateStep1()) return;
    this.currentStep.set(step);
    this.generalError.set(null);
    this.signatureMissing.set(false);
  }

  protected clearSignature(): void {
    this.signaturePad()?.clear();
    this.signatureMissing.set(false);
  }

  async submit(): Promise<void> {
    if (this.submitting()) return;

    if (!this.validateStep1()) {
      this.currentStep.set(1);
      return;
    }

    const pad = this.signaturePad();
    if (!pad || pad.isEmpty()) {
      this.signatureMissing.set(true);
      return;
    }

    this.submitting.set(true);
    this.generalError.set(null);

    try {
      const raw = this.form.getRawValue();
      const treatmentsPayload = raw.treatments.map((entry) => ({
        treatment_id: entry.treatment_id,
        is_suitable: entry.is_suitable!,
        unsuitability_reason: entry.is_suitable === false ? (entry.unsuitability_reason ?? null) : null,
        treatment_consent: entry.is_suitable === true ? !!entry.treatment_consent : false,
        notes: entry.notes === '' ? null : entry.notes,
      }));

      await this.consents.submitWizard({
        user_id: Number(this.id()),
        rgpd: {
          clinical_photos_consent: raw.rgpd.clinical_photos_consent!,
          marketing_data_consent: raw.rgpd.marketing_data_consent!,
          commercial_images_consent: raw.rgpd.commercial_images_consent!,
        },
        treatments: treatmentsPayload,
        signature_base64: pad.toDataURL('image/png'),
        notes: raw.notes === '' ? null : raw.notes,
      });

      this.notifications.toast.success('Consentimiento firmado y archivado.');
      void this.router.navigateByUrl(`/panel/clientes/${this.id()}`);
    } catch (error) {
      const message = (error as HttpErrorResponse).error?.message ?? 'Los datos del consent no son válidos.';
      this.notifications.toast.error(message);
      this.currentStep.set(1);
    } finally {
      this.submitting.set(false);
    }
  }

  protected treatmentName(index: number): string {
    const treatment = this.treatments()[index];
    return treatment?.name ?? '—';
  }

  private async load(userId: number): Promise<void> {
    this.loading.set(true);
    this.loadError.set(null);
    try {
      const [client, treatments] = await Promise.all([
        this.clients.getById(userId),
        this.consents.listActiveTreatments(),
      ]);

      if (!client.roles.includes('cliente')) {
        this.loadError.set('Este usuario no es un cliente del centro.');
        return;
      }
      if (treatments.length === 0) {
        this.loadError.set('El centro no tiene tratamientos activos: no se puede iniciar un consent.');
        return;
      }

      this.client.set(client);
      this.treatments.set(treatments);
      this.rebuildTreatmentsArray(treatments);
    } catch {
      this.loadError.set('No se han podido cargar los datos necesarios para firmar el consent.');
    } finally {
      this.loading.set(false);
    }
  }

  private rebuildTreatmentsArray(treatments: TreatmentSummary[]): void {
    const array = this.treatmentsArray;
    array.clear();
    for (const treatment of treatments) {
      array.push(this.buildTreatmentGroup(treatment.id));
    }
  }

  private buildTreatmentGroup(treatmentId: number): FormGroup<TreatmentFormControls> {
    return this.fb.nonNullable.group<TreatmentFormControls>({
      treatment_id: this.fb.nonNullable.control(treatmentId),
      is_suitable: this.fb.nonNullable.control<boolean | null>(null, Validators.required),
      unsuitability_reason: this.fb.nonNullable.control(''),
      treatment_consent: this.fb.nonNullable.control<boolean | null>(null),
      notes: this.fb.nonNullable.control(''),
    });
  }

  //al marcar "apto" preseleccionamos "acepta": en la mayoria de visitas el cliente sí consiente, ahorra un click
  protected onSuitabilityChange(index: number, isSuitable: boolean): void {
    const consent = this.treatmentsArray.at(index).controls.treatment_consent;
    if (isSuitable) {
      consent.setValue(true);
      consent.setErrors(null);
    } else {
      consent.setValue(null);
    }
  }

  private validateStep1(): boolean {
    this.form.markAllAsTouched();

    let valid = true;
    const rgpd = this.form.controls.rgpd;
    if (rgpd.controls.clinical_photos_consent.value === null) valid = false;
    if (rgpd.controls.marketing_data_consent.value === null) valid = false;
    if (rgpd.controls.commercial_images_consent.value === null) valid = false;

    for (const group of this.treatmentsArray.controls) {
      const isSuitable = group.controls.is_suitable.value;
      if (isSuitable === null) {
        valid = false;
        continue;
      }
      if (isSuitable === false) {
        if (group.controls.unsuitability_reason.value.trim() === '') {
          group.controls.unsuitability_reason.setErrors({ required: true });
          valid = false;
        }
      } else if (group.controls.treatment_consent.value === null) {
        group.controls.treatment_consent.setErrors({ required: true });
        valid = false;
      }
    }

    if (!valid) {
      this.generalError.set('Revisa los campos marcados. Faltan respuestas obligatorias.');
    } else {
      this.generalError.set(null);
    }

    return valid;
  }

}
