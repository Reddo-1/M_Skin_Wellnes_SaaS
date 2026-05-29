import { Component, computed, effect, inject, input, output } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { LookupService } from '../../../../../core/services/lookup.service';
import { hasFieldError, hasValidationError } from '../../../../../core/utils/form.util';
import { ModalComponent } from '../../../../../shared/ui/modal/modal.component';
import { SelectComponent, SelectOption } from '../../../../../shared/ui/select/select.component';
import { InputComponent } from '../../../../../shared/ui/input/input.component';

export interface InventoryEntryFormValue {
  movement_type_id: number;
  package_quantity: number;
  reason: string | null;
}

@Component({
  selector: 'app-inventory-entry-modal',
  standalone: true,
  imports: [ReactiveFormsModule, ModalComponent, SelectComponent, InputComponent],
  templateUrl: './inventory-entry-modal.component.html',
})
export class InventoryEntryModalComponent {
  readonly productId = input<number | null>(null);
  readonly productName = input<string>('');
  readonly submitting = input.required<boolean>();

  readonly close = output<void>();
  readonly formSubmit = output<InventoryEntryFormValue>();

  private readonly fb = inject(FormBuilder);
  protected readonly lookup = inject(LookupService);

  protected readonly typeOptions = computed<SelectOption[]>(() =>
    this.lookup
      .stockMovementTypes()
      .filter((type) => type.name === 'entrada' || type.name === 'devolucion')
      .map((type) => ({
        value: String(type.id),
        label: type.name === 'devolucion' ? 'Devolución' : 'Entrada',
      })),
  );

  protected readonly form = this.fb.nonNullable.group({
    movement_type_id: ['', Validators.required],
    package_quantity: [1, [Validators.required, Validators.min(1)]],
    reason: ['', Validators.maxLength(200)],
  });

  constructor() {
    effect(() => {
      if (this.productId() === null) return;
      const defaultType = this.typeOptions().find((option) => option.label === 'Entrada');
      this.form.reset({
        movement_type_id: defaultType?.value ?? '',
        package_quantity: 1,
        reason: '',
      });
    });
  }

  protected onSubmit(): void {
    if (this.submitting()) return;
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    const raw = this.form.getRawValue();
    this.formSubmit.emit({
      movement_type_id: Number(raw.movement_type_id),
      package_quantity: raw.package_quantity,
      reason: raw.reason.trim() === '' ? null : raw.reason,
    });
  }

  protected hasFieldError(field: 'movement_type_id' | 'package_quantity' | 'reason'): boolean {
    return hasFieldError(this.form.controls[field]);
  }

  protected hasValidationError(field: 'movement_type_id' | 'package_quantity' | 'reason', key: string): boolean {
    return hasValidationError(this.form.controls[field], key);
  }
}
