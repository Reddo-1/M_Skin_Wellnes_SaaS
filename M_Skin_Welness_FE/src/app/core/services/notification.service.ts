import { Injectable, signal } from '@angular/core';
import { AlertVariant } from '../../shared/ui/alert/alert.component';
import {
  ActiveModal,
  ModalAlertConfig,
  ModalConfirmConfig,
  ModalKind,
  Toast,
  ToastOptions,
} from '../models/notification.model';

const DEFAULT_TOAST_DURATION_MS = 5000;
const ERROR_TOAST_DURATION_MS = 8000;
const MAX_VISIBLE_TOASTS = 5;

//toast.*/await modal.* actualizan el signal que pinta NotificationContainer; al cerrar se vacía y, si es modal, se resuelve su promise
@Injectable({ providedIn: 'root' })
export class NotificationService {
  readonly toasts = signal<Toast[]>([]);
  readonly activeModal = signal<ActiveModal | null>(null);

  private nextId = 1;

  readonly toast = {
    success: this.toastFn('success'),
    error: this.toastFn('error'),
    warning: this.toastFn('warning'),
    info: this.toastFn('info'),
  };

  readonly modal = {
    alert: async (config: ModalAlertConfig): Promise<void> => {
      await this.openModal('alert', config, config.confirmText ?? 'Aceptar', '');
    },
    confirm: (config: ModalConfirmConfig): Promise<boolean> =>
      this.openModal(
        'confirm',
        config,
        config.confirmText ?? 'Confirmar',
        config.cancelText ?? 'Cancelar',
      ),
  };

  dismissToast(id: number): void {
    this.toasts.update((list) => list.filter((t) => t.id !== id));
  }

  resolveModal(value: boolean): void {
    const current = this.activeModal();
    if (current === null) {
      return;
    }
    current.resolve(value);
    this.activeModal.set(null);
  }

  private toastFn(variant: AlertVariant) {
    return (message: string, options?: ToastOptions) => this.pushToast(variant, message, options);
  }

  private openModal(
    kind: ModalKind,
    config: ModalAlertConfig,
    confirmText: string,
    cancelText: string,
  ): Promise<boolean> {
    return new Promise<boolean>((resolve) => {
      this.activeModal.set({
        kind,
        variant: config.variant,
        title: config.title,
        message: config.message,
        confirmText,
        cancelText,
        resolve,
      });
    });
  }

  private pushToast(variant: AlertVariant, message: string, options?: ToastOptions): void {
    const last = this.toasts().at(-1);
    if (last !== undefined && last.variant === variant && last.message === message) {
      return;
    }

    const id = this.nextId++;
    const durationMs =
      options?.durationMs ?? (variant === 'error' ? ERROR_TOAST_DURATION_MS : DEFAULT_TOAST_DURATION_MS);
    const toast: Toast = {
      id,
      variant,
      message,
      title: options?.title,
      durationMs,
    };

    this.toasts.update((list) => {
      const next = [...list, toast];
      return next.length > MAX_VISIBLE_TOASTS ? next.slice(next.length - MAX_VISIBLE_TOASTS) : next;
    });

    setTimeout(() => this.dismissToast(id), durationMs);
  }
}
