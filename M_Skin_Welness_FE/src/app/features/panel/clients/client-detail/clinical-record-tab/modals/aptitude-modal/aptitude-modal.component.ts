import { Component, effect, inject, input, output, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { TreatmentConsentSummary, UpdateAptitudeData } from '../../../../../../../core/services/consent.service';
import { hasFieldError, hasValidationError } from '../../../../../../../core/utils/form.util';
import { InputComponent } from '../../../../../../../shared/ui/input/input.component';
import { ModalComponent } from '../../../../../../../shared/ui/modal/modal.component';
import { RadioGroupComponent, RadioOption } from '../../../../../../../shared/ui/radio-group/radio-group.component';
import { TextareaComponent } from '../../../../../../../shared/ui/textarea/textarea.component';

const SUITABILITY_OPTIONS: RadioOption<boolean>[] = [
  { value: true, label: 'Apto' },
  { value: false, label: 'No apto' },
];

@Component({
  selector: 'app-aptitude-modal',
  standalone: true,
  imports: [
    ReactiveFormsModule,
    ModalComponent,
    RadioGroupComponent,
    InputComponent,
    TextareaComponent,
  ],
  templateUrl: './aptitude-modal.component.html',
})
export class AptitudeModalComponent {
  readonly isOpen = input.required<boolean>();
  readonly treatmentConsent = input<TreatmentConsentSummary | null>(null);
  readonly submitting = input.required<boolean>();

  readonly close = output<void>();
  readonly formSubmit = output<UpdateAptitudeData>();

  private readonly fb = inject(FormBuilder);

  protected readonly suitabilityOptions = SUITABILITY_OPTIONS;

  //is_suitable como signal: el motivo se muestra de forma reactiva (zoneless no rastrea control.value en plantilla)
  protected readonly suitable = signal<boolean | null>(null);
  protected readonly suitabilityMissing = signal(false);

  protected readonly form = this.fb.nonNullable.group({
    unsuitability_reason: ['', [Validators.maxLength(150)]],
    notes: ['', [Validators.maxLength(2000)]],
  });

  constructor() {
    effect(() => {
      if (!this.isOpen()) return;
      const current = this.treatmentConsent();
      this.suitable.set(current?.is_suitable ?? null);
      this.suitabilityMissing.set(false);
      this.form.reset({
        unsuitability_reason: current?.unsuitability_reason ?? '',
        notes: current?.notes ?? '',
      });
    });
  }

  protected selectSuitability(choice: boolean): void {
    this.suitable.set(choice);
    this.suitabilityMissing.set(false);
    //al pasar a apto el motivo deja de ser obligatorio: limpia el error para no bloquear el envío
    if (choice) {
      this.form.controls.unsuitability_reason.setErrors(null);
    }
  }

  protected onSubmit(): void {
    if (this.submitting()) return;
    const suitable = this.suitable();
    if (suitable === null) {
      this.suitabilityMissing.set(true);
      return;
    }
    //no apto exige motivo (regla clínica, alineada con el BE)
    const reasonControl = this.form.controls.unsuitability_reason;
    if (suitable === false && reasonControl.value.trim() === '') {
      reasonControl.setErrors({ required: true });
      reasonControl.markAsTouched();
      return;
    }
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    const raw = this.form.getRawValue();
    const reason = raw.unsuitability_reason.trim();
    this.formSubmit.emit({
      is_suitable: suitable,
      unsuitability_reason: suitable === false ? reason : null,
      notes: raw.notes.trim() === '' ? null : raw.notes,
    });
  }

  protected hasFieldError(field: 'unsuitability_reason' | 'notes'): boolean {
    return hasFieldError(this.form.controls[field]);
  }

  protected hasValidationError(field: 'unsuitability_reason' | 'notes', key: string): boolean {
    return hasValidationError(this.form.controls[field], key);
  }
}
