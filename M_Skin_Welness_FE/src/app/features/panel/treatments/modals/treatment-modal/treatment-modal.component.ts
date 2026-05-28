import { Component, computed, effect, inject, input, output } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Treatment } from '../../../../../core/models/treatment.model';
import { LookupItem } from '../../../../../core/models/lookup.model';
import { LookupService } from '../../../../../core/services/lookup.service';
import { hasFieldError, hasValidationError } from '../../../../../core/utils/form.util';
import { ModalComponent } from '../../../../../shared/ui/modal/modal.component';
import { MultiSelectComponent } from '../../../../../shared/ui/multi-select/multi-select.component';

export interface TreatmentFormValue {
  name: string;
  duration_minutes: number;
  price: number;
  is_active: boolean;
  machine_ids: number[];
  role_ids: number[];
}

type TreatmentField = 'name' | 'duration_minutes' | 'price';

@Component({
  selector: 'app-treatment-modal',
  standalone: true,
  imports: [ReactiveFormsModule, ModalComponent, MultiSelectComponent],
  templateUrl: './treatment-modal.component.html',
})
export class TreatmentModalComponent {
  readonly isOpen = input.required<boolean>();
  readonly treatment = input<Treatment | null>(null);
  readonly machines = input<LookupItem[]>([]);
  readonly submitting = input.required<boolean>();

  readonly close = output<void>();
  readonly formSubmit = output<TreatmentFormValue>();

  private readonly fb = inject(FormBuilder);
  private readonly lookup = inject(LookupService);

  protected readonly isEdit = computed(() => this.treatment() !== null);

  protected readonly roleOptions = computed<LookupItem[]>(() =>
    this.lookup.roles().filter((role) => role.name !== 'cliente' && role.name !== 'superadmin'),
  );

  protected readonly form = this.fb.nonNullable.group({
    name: ['', [Validators.required, Validators.maxLength(120)]],
    duration_minutes: [30, [Validators.required, Validators.min(1)]],
    price: [0, [Validators.required, Validators.min(0)]],
    is_active: [true],
    machine_ids: this.fb.nonNullable.control<number[]>([]),
    role_ids: this.fb.nonNullable.control<number[]>([]),
  });

  constructor() {
    effect(() => {
      if (!this.isOpen()) return;
      const current = this.treatment();
      this.form.reset({
        name: current?.name ?? '',
        duration_minutes: current?.duration_minutes ?? 30,
        price: current ? Number(current.price) : 0,
        is_active: current?.is_active ?? true,
        machine_ids: current?.machines.map((machine) => machine.id) ?? [],
        role_ids: current?.authorized_roles.map((role) => role.id) ?? [],
      });
    });
  }

  protected onSubmit(): void {
    if (this.submitting()) return;
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    this.formSubmit.emit(this.form.getRawValue());
  }

  protected hasFieldError(field: TreatmentField): boolean {
    return hasFieldError(this.form.controls[field]);
  }

  protected hasValidationError(field: TreatmentField, key: string): boolean {
    return hasValidationError(this.form.controls[field], key);
  }
}
