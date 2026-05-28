import { Component, computed, effect, inject, input, output } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Product } from '../../../../../core/models/product.model';
import { hasFieldError, hasValidationError } from '../../../../../core/utils/form.util';
import { ModalComponent } from '../../../../../shared/ui/modal/modal.component';

export interface ProductFormValue {
  name: string;
  description: string | null;
  sale_price: number | null;
  cost_price: number | null;
  doses_per_package: number;
  minimum_stock: number;
  is_sellable: boolean;
  is_active: boolean;
}

type ProductField =
  | 'name'
  | 'description'
  | 'sale_price'
  | 'cost_price'
  | 'doses_per_package'
  | 'minimum_stock';

@Component({
  selector: 'app-product-modal',
  standalone: true,
  imports: [ReactiveFormsModule, ModalComponent],
  templateUrl: './product-modal.component.html',
})
export class ProductModalComponent {
  readonly isOpen = input.required<boolean>();
  readonly product = input<Product | null>(null);
  readonly submitting = input.required<boolean>();

  readonly close = output<void>();
  readonly formSubmit = output<ProductFormValue>();

  private readonly fb = inject(FormBuilder);

  protected readonly isEdit = computed(() => this.product() !== null);

  protected readonly form = this.fb.nonNullable.group({
    name: ['', [Validators.required, Validators.maxLength(120)]],
    description: ['', Validators.maxLength(5000)],
    sale_price: this.fb.control<number | null>(null, Validators.min(0)),
    cost_price: this.fb.control<number | null>(null, Validators.min(0)),
    doses_per_package: [1, [Validators.required, Validators.min(1)]],
    minimum_stock: [0, [Validators.required, Validators.min(0)]],
    is_sellable: [true],
    is_active: [true],
  });

  constructor() {
    effect(() => {
      if (!this.isOpen()) return;
      const current = this.product();
      this.form.reset({
        name: current?.name ?? '',
        description: current?.description ?? '',
        sale_price: current?.sale_price != null ? Number(current.sale_price) : null,
        cost_price: current?.cost_price != null ? Number(current.cost_price) : null,
        doses_per_package: current?.doses_per_package ?? 1,
        minimum_stock: current ? Number(current.minimum_stock) : 0,
        is_sellable: current?.is_sellable ?? true,
        is_active: current?.is_active ?? true,
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
      name: raw.name,
      description: raw.description.trim() === '' ? null : raw.description,
      sale_price: raw.sale_price,
      cost_price: raw.cost_price,
      doses_per_package: raw.doses_per_package,
      minimum_stock: raw.minimum_stock,
      is_sellable: raw.is_sellable,
      is_active: raw.is_active,
    });
  }

  protected hasFieldError(field: ProductField): boolean {
    return hasFieldError(this.form.controls[field]);
  }

  protected hasValidationError(field: ProductField, key: string): boolean {
    return hasValidationError(this.form.controls[field], key);
  }
}
