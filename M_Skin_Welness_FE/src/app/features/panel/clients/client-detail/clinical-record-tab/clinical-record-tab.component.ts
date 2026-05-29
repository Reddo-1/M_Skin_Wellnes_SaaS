import { DatePipe } from '@angular/common';
import { HttpErrorResponse } from '@angular/common/http';
import { Component, computed, effect, inject, input, signal } from '@angular/core';
import { ClinicalRecordSummary } from '../../../../../core/models/clinical-record.model';
import { BodyType } from '../../../../../core/models/skin-evaluation.model';
import { User } from '../../../../../core/models/user.model';
import { AuthService } from '../../../../../core/services/auth.service';
import { ClinicalRecordService } from '../../../../../core/services/clinical-record.service';
import { NotificationService } from '../../../../../core/services/notification.service';
import { GENERIC_ERROR, loadResourceError } from '../../../../../core/utils/form.util';
import { SegmentedControlComponent, SegmentedControlOption } from '../../../../../shared/ui/segmented-control/segmented-control.component';
import { ClinicalRecordFormValue, ClinicalRecordModalComponent } from './modals/clinical-record-modal/clinical-record-modal.component';

const BODY_TYPE_OPTIONS: SegmentedControlOption<BodyType>[] = [
  { value: 'facial', label: 'Facial' },
  { value: 'corporal', label: 'Corporal' },
];

@Component({
  selector: 'app-clinical-record-tab',
  standalone: true,
  imports: [DatePipe, ClinicalRecordModalComponent, SegmentedControlComponent],
  templateUrl: './clinical-record-tab.component.html',
})
export class ClinicalRecordTabComponent {
  readonly client = input.required<User>();

  private readonly records = inject(ClinicalRecordService);
  private readonly notifications = inject(NotificationService);
  protected readonly auth = inject(AuthService);

  protected readonly bodyTypeOptions = BODY_TYPE_OPTIONS;

  protected readonly items = signal<ClinicalRecordSummary[]>([]);
  protected readonly loading = signal(false);
  protected readonly errorMessage = signal<string | null>(null);
  protected readonly selectedBodyType = signal<BodyType>('facial');

  protected readonly modalOpen = signal(false);
  protected readonly recordToEdit = signal<ClinicalRecordSummary | null>(null);
  protected readonly submitting = signal(false);

  protected readonly currentRecord = computed(() => {
    const target = this.selectedBodyType();
    return this.items().find((record) => record.body_type === target) ?? null;
  });

  constructor() {
    effect(() => {
      const userId = this.client().id;
      void this.load(userId);
    });
  }

  protected selectBodyType(value: BodyType): void {
    this.selectedBodyType.set(value);
  }

  protected openCreate(): void {
    this.recordToEdit.set(null);
    this.modalOpen.set(true);
  }

  protected openEdit(): void {
    const record = this.currentRecord();
    if (record !== null) {
      this.recordToEdit.set(record);
      this.modalOpen.set(true);
    }
  }

  protected closeModal(): void {
    if (this.submitting()) return;
    this.modalOpen.set(false);
  }

  protected async submit(value: ClinicalRecordFormValue): Promise<void> {
    const record = this.recordToEdit();
    const generalNotes = value.general_notes.trim() === '' ? null : value.general_notes;

    this.submitting.set(true);
    try {
      if (record !== null) {
        const updated = await this.records.update(record.id, { general_notes: generalNotes });
        this.items.update((current) =>
          current.map((item) => (item.id === updated.id ? updated : item)),
        );
        this.notifications.toast.success('Ficha actualizada.');
      } else {
        const bodyType = this.selectedBodyType();
        const created = await this.records.create({
          user_id: this.client().id,
          body_type: bodyType,
          general_notes: generalNotes,
        });
        this.items.update((current) => [...current, created]);
        this.notifications.toast.success(`Ficha ${bodyType} creada.`);
      }
      this.modalOpen.set(false);
    } catch (error) {
      const message = (error as HttpErrorResponse).error?.message ?? GENERIC_ERROR;
      this.notifications.toast.error(message);
    } finally {
      this.submitting.set(false);
    }
  }

  private async load(userId: number): Promise<void> {
    this.loading.set(true);
    this.errorMessage.set(null);
    try {
      const records = await this.records.listByUser(userId);
      this.items.set(records);
    } catch {
      const message = loadResourceError('las fichas clínicas');
      this.errorMessage.set(message);
      this.notifications.toast.error(message);
    } finally {
      this.loading.set(false);
    }
  }
}
