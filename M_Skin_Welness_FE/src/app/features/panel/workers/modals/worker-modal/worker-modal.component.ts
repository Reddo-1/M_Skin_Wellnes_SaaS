import { Component, computed, effect, inject, input, output } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { User } from '../../../../../core/models/user.model';
import { LookupItem } from '../../../../../core/models/lookup.model';
import { LookupService } from '../../../../../core/services/lookup.service';
import { hasFieldError, hasValidationError } from '../../../../../core/utils/form.util';
import { ModalComponent } from '../../../../../shared/ui/modal/modal.component';
import { MultiSelectComponent } from '../../../../../shared/ui/multi-select/multi-select.component';
import { InputComponent } from '../../../../../shared/ui/input/input.component';
import { DatePickerComponent } from '../../../../../shared/ui/date-picker/date-picker.component';
import { ToggleComponent } from '../../../../../shared/ui/toggle/toggle.component';

export interface WorkerFormValue {
  name: string;
  email: string;
  phone: string;
  birth_date: string;
  role_ids: number[];
  password: string;
  is_active: boolean;
}

type WorkerField = 'name' | 'email' | 'phone' | 'birth_date' | 'password';

@Component({
  selector: 'app-worker-modal',
  standalone: true,
  imports: [
    ReactiveFormsModule,
    ModalComponent,
    MultiSelectComponent,
    InputComponent,
    DatePickerComponent,
    ToggleComponent,
  ],
  templateUrl: './worker-modal.component.html',
})
export class WorkerModalComponent {
  readonly isOpen = input.required<boolean>();
  readonly worker = input<User | null>(null);
  readonly submitting = input.required<boolean>();

  readonly close = output<void>();
  readonly formSubmit = output<WorkerFormValue>();

  private readonly fb = inject(FormBuilder);
  private readonly lookup = inject(LookupService);

  protected readonly isEdit = computed(() => this.worker() !== null);

  protected readonly roleOptions = computed<LookupItem[]>(() =>
    this.lookup.roles().filter((role) => role.name !== 'cliente' && role.name !== 'superadmin'),
  );

  protected readonly form = this.fb.nonNullable.group({
    name: ['', [Validators.required, Validators.maxLength(120)]],
    email: ['', [Validators.required, Validators.email, Validators.maxLength(150)]],
    phone: ['', [Validators.maxLength(30)]],
    birth_date: [''],
    role_ids: this.fb.nonNullable.control<number[]>([]),
    password: ['', [Validators.minLength(8), Validators.maxLength(255)]],
    is_active: [true],
  });

  constructor() {
    effect(() => {
      if (!this.isOpen()) return;
      const current = this.worker();
      this.form.reset({
        name: current?.name ?? '',
        email: current?.email ?? '',
        phone: current?.phone ?? '',
        birth_date: current?.birth_date ?? '',
        role_ids: [],
        password: '',
        is_active: current?.is_active ?? true,
      });

      const roleControl = this.form.controls.role_ids;
      if (current === null) {
        roleControl.setValidators([Validators.required, Validators.minLength(1)]);
      } else {
        roleControl.clearValidators();
      }
      roleControl.updateValueAndValidity({ emitEvent: false });
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

  protected hasFieldError(field: WorkerField): boolean {
    return hasFieldError(this.form.controls[field]);
  }

  protected hasValidationError(field: WorkerField, key: string): boolean {
    return hasValidationError(this.form.controls[field], key);
  }

  protected hasRoleError(): boolean {
    const control = this.form.controls.role_ids;
    return control.touched && control.invalid;
  }
}
