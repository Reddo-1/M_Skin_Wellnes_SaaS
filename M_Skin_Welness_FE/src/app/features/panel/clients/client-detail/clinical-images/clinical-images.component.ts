import { HttpErrorResponse } from '@angular/common/http';
import { Component, computed, inject, input, linkedSignal, signal } from '@angular/core';
import { BodyType, ClinicalImage } from '../../../../../core/models/skin-evaluation.model';
import { NotificationService } from '../../../../../core/services/notification.service';
import { UserFileService } from '../../../../../core/services/user-file.service';
import { apiError } from '../../../../../core/utils/form.util';

interface ImageSlot {
  category: string;
  label: string;
}

const FACIAL_SLOTS: ImageSlot[] = [
  { category: 'facial_frontal', label: 'Frontal' },
  { category: 'facial_izquierdo', label: 'Lateral izquierdo' },
  { category: 'facial_derecho', label: 'Lateral derecho' },
];

const CORPORAL_SLOTS: ImageSlot[] = [
  { category: 'corporal_frontal', label: 'Frontal' },
  { category: 'corporal_trasero', label: 'Trasero' },
  { category: 'corporal_izquierdo', label: 'Lateral izquierdo' },
  { category: 'corporal_derecho', label: 'Lateral derecho' },
];

const MAX_SIZE_BYTES = 5 * 1024 * 1024;
const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

@Component({
  selector: 'app-clinical-images',
  standalone: true,
  templateUrl: './clinical-images.component.html',
})
export class ClinicalImagesComponent {
  readonly evaluationId = input.required<number>();
  readonly userId = input.required<number>();
  readonly bodyType = input.required<BodyType>();
  readonly canManage = input.required<boolean>();
  readonly initialImages = input<ClinicalImage[]>([]);

  private readonly userFiles = inject(UserFileService);
  private readonly notifications = inject(NotificationService);

  //se re-siembra cuando el padre recarga pero admite ediciones locales entre tanto
  protected readonly images = linkedSignal(() => this.initialImages());
  protected readonly busyCategory = signal<string | null>(null);

  protected readonly slots = computed<ImageSlot[]>(() =>
    this.bodyType() === 'facial' ? FACIAL_SLOTS : CORPORAL_SLOTS,
  );

  protected imageFor(category: string): ClinicalImage | null {
    return this.images().find((image) => image.category === category) ?? null;
  }

  async onFileSelected(category: string, event: Event): Promise<void> {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    input.value = '';
    if (!file || this.busyCategory() !== null) return;

    if (!ALLOWED_TYPES.includes(file.type)) {
      this.notifications.toast.error('La imagen debe ser un archivo JPG, PNG o WEBP.');
      return;
    }
    if (file.size > MAX_SIZE_BYTES) {
      this.notifications.toast.error('La imagen supera el tamaño máximo de 5 MB.');
      return;
    }

    const previous = this.imageFor(category);
    this.busyCategory.set(category);
    try {
      const uploaded = await this.userFiles.upload({
        user_id: this.userId(),
        category,
        skin_evaluation_id: this.evaluationId(),
        notes: null,
        file,
      });
      //reemplazo = subir nueva y borrar la anterior (user-files no tiene update)
      if (previous !== null) {
        await this.userFiles.delete(previous.id);
      }
      this.images.update((list) => [
        ...list.filter((image) => image.category !== category),
        { id: uploaded.id, category: uploaded.category, url: uploaded.url },
      ]);
      this.notifications.toast.success('Imagen guardada.');
    } catch (error) {
      const httpError = error as HttpErrorResponse;
      //413: Nginx puede cortar antes de que Laravel responda con su 422
      const message = httpError.status === 413
        ? 'La imagen es demasiado grande. El máximo permitido son 5 MB.'
        : apiError(error);
      this.notifications.toast.error(message);
    } finally {
      this.busyCategory.set(null);
    }
  }

  async removeImage(category: string): Promise<void> {
    const target = this.imageFor(category);
    if (target === null || this.busyCategory() !== null) return;

    const confirmed = await this.notifications.modal.confirm({
      variant: 'warning',
      title: '¿Eliminar la imagen?',
      message: 'Esta acción no se puede deshacer.',
      confirmText: 'Sí, eliminar',
      cancelText: 'Cancelar',
    });
    if (!confirmed) return;

    this.busyCategory.set(category);
    try {
      await this.userFiles.delete(target.id);
      this.images.update((list) => list.filter((image) => image.category !== category));
      this.notifications.toast.success('Imagen eliminada.');
    } catch (error) {
      this.notifications.toast.error(apiError(error));
    } finally {
      this.busyCategory.set(null);
    }
  }
}
