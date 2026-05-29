import { DatePipe } from '@angular/common';
import { HttpErrorResponse } from '@angular/common/http';
import { Component, computed, effect, inject, input, signal } from '@angular/core';
import { ClinicalRecordSummary } from '../../../../../core/models/clinical-record.model';
import { SkinEvaluationSummary } from '../../../../../core/models/skin-evaluation.model';
import { User } from '../../../../../core/models/user.model';
import { AuthService } from '../../../../../core/services/auth.service';
import { ClinicalRecordService } from '../../../../../core/services/clinical-record.service';
import { NotificationService } from '../../../../../core/services/notification.service';
import { SkinEvaluationService } from '../../../../../core/services/skin-evaluation.service';
import { GENERIC_ERROR, loadResourceError } from '../../../../../core/utils/form.util';
import { AlertComponent } from '../../../../../shared/ui/alert/alert.component';
import { SkinEvaluationFormValue, SkinEvaluationModalComponent } from './modals/skin-evaluation-modal/skin-evaluation-modal.component';

@Component({
  selector: 'app-skin-evaluations-tab',
  standalone: true,
  imports: [DatePipe, AlertComponent, SkinEvaluationModalComponent],
  templateUrl: './skin-evaluations-tab.component.html',
})
export class SkinEvaluationsTabComponent {
  readonly client = input.required<User>();

  private readonly evaluations = inject(SkinEvaluationService);
  private readonly records = inject(ClinicalRecordService);
  private readonly notifications = inject(NotificationService);
  protected readonly auth = inject(AuthService);

  protected readonly items = signal<SkinEvaluationSummary[]>([]);
  protected readonly clinicalRecords = signal<ClinicalRecordSummary[]>([]);
  protected readonly loading = signal(false);
  protected readonly errorMessage = signal<string | null>(null);

  protected readonly modalOpen = signal(false);
  protected readonly evaluationToEdit = signal<SkinEvaluationSummary | null>(null);
  protected readonly submitting = signal(false);

  protected readonly canCreate = computed(
    () => this.auth.hasPermission('skin_evaluations.create') && this.clinicalRecords().length > 0,
  );

  constructor() {
    effect(() => {
      const userId = this.client().id;
      void this.load(userId);
    });
  }

  protected openCreate(): void {
    this.evaluationToEdit.set(null);
    this.modalOpen.set(true);
  }

  protected openEdit(evaluation: SkinEvaluationSummary): void {
    this.evaluationToEdit.set(evaluation);
    this.modalOpen.set(true);
  }

  protected closeModal(): void {
    if (this.submitting()) return;
    this.modalOpen.set(false);
  }

  protected async submit(value: SkinEvaluationFormValue): Promise<void> {
    const evaluation = this.evaluationToEdit();

    this.submitting.set(true);
    try {
      if (evaluation !== null) {
        const updated = await this.evaluations.update(evaluation.id, {
          skin_type_id: value.skin_type_id,
          evaluation_date: value.evaluation_date,
          general_notes: value.general_notes,
          variation_ids: value.variation_ids,
        });
        this.items.update((current) =>
          current.map((item) => (item.id === updated.id ? updated : item)),
        );
        this.notifications.toast.success('Evaluación actualizada.');
      } else {
        const created = await this.evaluations.create({
          client_profile_id: value.client_profile_id,
          skin_type_id: value.skin_type_id,
          evaluation_date: value.evaluation_date,
          general_notes: value.general_notes,
          variation_ids: value.variation_ids,
        });
        this.items.update((current) => [created, ...current]);
        this.notifications.toast.success('Evaluación registrada.');
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
      const [items, records] = await Promise.all([
        this.evaluations.listByUser(userId),
        this.records.listByUser(userId),
      ]);
      this.items.set(items);
      this.clinicalRecords.set(records);
    } catch {
      const message = loadResourceError('las evaluaciones de piel');
      this.errorMessage.set(message);
      this.notifications.toast.error(message);
    } finally {
      this.loading.set(false);
    }
  }
}
