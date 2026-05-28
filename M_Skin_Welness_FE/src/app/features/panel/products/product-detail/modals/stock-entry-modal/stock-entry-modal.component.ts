import { Component, computed, effect, inject, input, output } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Product } from '../../../../../../core/models/product.model';
import { LookupService } from '../../../../../../core/services/lookup.service';
import { hasFieldError, hasValidationError } from '../../../../../../core/utils/form.util';
import { ModalComponent } from '../../../../../../shared/ui/modal/modal.component';
import { SelectComponent, SelectOption } from '../../../../../../shared/ui/select/select.component';

export interface StockEntryFormValue {
  movement_type_id: number;
  package_quantity: number;
  reason: string | null;
}

type EntryField = 'movement_type_id' | 'package_quantity' | 'reason';

@Component({
  selector: 'app-stock-entry-modal',
  standalone: true,
  imports: [ReactiveFormsModule, ModalComponent, SelectComponent],
  templateUrl: './stock-entry-modal.component.html',
})
export class StockEntryModalComponent {
  readonly isOpen = input.required<boolean>();
  readonly product = input<Product | null>(null);
  readonly submitting = input.required<boolean>();

  readonly close = output<void>();
  readonly formSubmit = output<StockEntryFormValue>();

  private readonly fb = inject(FormBuilder);
  protected readonly lookup = inject(LookupService);

  protected readonly typeOptions = computed<SelectOption[]>(() =>
    this.lookup.stockMovementTypes()
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
      if (!this.isOpen()) return;
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

  protected hasFieldError(field: EntryField): boolean {
    return hasFieldError(this.form.controls[field]);
  }

  protected hasValidationError(field: EntryField, key: string): boolean {
    return hasValidationError(this.form.controls[field], key);
  }
}
