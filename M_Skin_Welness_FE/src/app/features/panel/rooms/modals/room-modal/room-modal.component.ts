import { Component, computed, effect, inject, input, output } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Room } from '../../../../../core/models/room.model';
import { hasFieldError, hasValidationError } from '../../../../../core/utils/form.util';
import { ModalComponent } from '../../../../../shared/ui/modal/modal.component';
import { InputComponent } from '../../../../../shared/ui/input/input.component';
import { ToggleComponent } from '../../../../../shared/ui/toggle/toggle.component';

export interface RoomFormValue {
  name: string;
  is_active: boolean;
}

@Component({
  selector: 'app-room-modal',
  standalone: true,
  imports: [ReactiveFormsModule, ModalComponent, InputComponent, ToggleComponent],
  templateUrl: './room-modal.component.html',
})
export class RoomModalComponent {
  readonly isOpen = input.required<boolean>();
  readonly room = input<Room | null>(null);
  readonly submitting = input.required<boolean>();

  readonly close = output<void>();
  readonly formSubmit = output<RoomFormValue>();

  private readonly fb = inject(FormBuilder);

  protected readonly isEdit = computed(() => this.room() !== null);

  protected readonly form = this.fb.nonNullable.group({
    name: ['', [Validators.required, Validators.maxLength(120)]],
    is_active: [true],
  });

  constructor() {
    effect(() => {
      if (!this.isOpen()) return;
      const current = this.room();
      this.form.reset({
        name: current?.name ?? '',
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
