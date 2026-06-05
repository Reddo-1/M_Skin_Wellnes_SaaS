import { AlertVariant } from '../../shared/ui/alert/alert.component';

export interface ToastOptions {
  title?: string;
  durationMs?: number;
}

export interface Toast {
  id: number;
  variant: AlertVariant;
  message: string;
  title?: string;
  durationMs: number;
}

export interface ModalAlertConfig {
  variant: AlertVariant;
  title: string;
  message: string;
  confirmText?: string;
}

//confirm = alert con botón de cancelar; el caller espera una Promise<boolean>
export interface ModalConfirmConfig extends ModalAlertConfig {
  cancelText?: string;
}

export type ModalKind = 'alert' | 'confirm';

//resolve cierra la Promise pendiente con el valor que pulse el usuario
export interface ActiveModal {
  kind: ModalKind;
  variant: AlertVariant;
  title: string;
  message: string;
  confirmText: string;
  cancelText: string;
  resolve: (value: boolean) => void;
}
