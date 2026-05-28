import { Component, computed, effect, inject, input, output } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Machine } from '../../../../../core/models/machine.model';
import { LookupItem } from '../../../../../core/models/lookup.model';
import { hasFieldError, hasValidationError } from '../../../../../core/utils/form.util';
import { ModalComponent } from '../../../../../shared/ui/modal/modal.component';

export interface MachineFormValue {
  name: string;
  is_mobile: boolean;
  fixed_room_id: number | null;
  is_active: boolean;
}

@Component({
  selector: 'app-machine-modal',
  standalone: true,
  imports: [ReactiveFormsModule, ModalComponent],
  templateUrl: './machine-modal.component.html',
})
export class MachineModalComponent {
  readonly isOpen = input.required<boolean>();
  readonly machine = input<Machine | null>(null);
  readonly rooms = input<LookupItem[]>([]);
  readonly submitting = input.required<boolean>();

  readonly close = output<void>();
  readonly formSubmit = output<MachineFormValue>();

  private readonly fb = inject(FormBuilder);

  protected readonly isEdit = computed(() => this.machine() !== null);

  protected readonly form = this.fb.nonNullable.group({
    name: ['', [Validators.required, Validators.maxLength(120)]],
    is_mobile: [false],
    fixed_room_id: [''],
    is_active: [true],
  });

  constructor() {
    effect(() => {
      if (!this.isOpen()) return;
      const current = this.machine();
      this.form.reset({
        name: current?.name ?? '',
        is_mobile: current?.is_mobile ?? false,
        fixed_room_id: current?.fixed_room_id != null ? String(current.fixed_room_id) : '',
        is_active: current?.is_active ?? true,
      });
    });
  }

  protected onMobileToggle(): void {
    if (this.form.controls.is_mobile.value) {
      this.form.controls.fixed_room_id.setValue('');
    }
  }

  protected onSubmit(): void {
    if (this.submitting()) return;
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    const raw = this.form.getRawValue();
    const fixedRoomId = raw.is_mobile || raw.fixed_room_id === '' ? null : Number(raw.fixed_room_id);
    this.formSubmit.emit({
      name: raw.name,
      is_mobile: raw.is_mobile,
      fixed_room_id: fixedRoomId,
      is_active: raw.is_active,
    });
  }

  protected hasFieldError(field: 'name'): boolean {
    return hasFieldError(this.form.controls[field]);
  }

  protected hasValidationError(field: 'name', key: string): boolean {
    return hasValidationError(this.form.controls[field], key);
  }
}
