import { Component, effect, inject, input, output } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Product } from '../../../../../../core/models/product.model';
import { hasFieldError, hasValidationError } from '../../../../../../core/utils/form.util';
import { ModalComponent } from '../../../../../../shared/ui/modal/modal.component';
import { InputComponent } from '../../../../../../shared/ui/input/input.component';

export interface StockAdjustFormValue {
  new_quantity: number;
  reason: string;
}

type AdjustField = 'new_quantity' | 'reason';

@Component({
  selector: 'app-stock-adjust-modal',
  standalone: true,
  imports: [ReactiveFormsModule, ModalComponent, InputComponent],
  templateUrl: './stock-adjust-modal.component.html',
})
export class StockAdjustModalComponent {
  readonly isOpen = input.required<boolean>();
  readonly product = input<Product | null>(null);
  readonly submitting = input.required<boolean>();

  readonly close = output<void>();
  readonly formSubmit = output<StockAdjustFormValue>();

  private readonly fb = inject(FormBuilder);

  protected readonly form = this.fb.nonNullable.group({
    new_quantity: [0, [Validators.required, Validators.min(0)]],
    reason: ['', [Validators.required, Validators.maxLength(200)]],
  });

  constructor() {
    effect(() => {
      if (!this.isOpen()) return;
      const current = this.product();
      this.form.reset({
        new_quantity: current?.stock ? Number(current.stock.current_quantity) : 0,
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
    this.formSubmit.emit(this.form.getRawValue());
  }

  protected hasFieldError(field: AdjustField): boolean {
    return hasFieldError(this.form.controls[field]);
  }

  protected hasValidationError(field: AdjustField, key: string): boolean {
    return hasValidationError(this.form.controls[field], key);
  }
}
