import { AlertVariant } from '../../shared/ui/alert/alert.component';

//Opciones que el caller puede pasar al lanzar un toast (todas opcionales)
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

//Modal de confirmación (alert + botón de cancelar). Devuelve Promise<boolean>.
export interface ModalConfirmConfig extends ModalAlertConfig {
  cancelText?: string;
}

export type ModalKind = 'alert' | 'confirm';

//Modal activo en pantalla. resolve cierra la Promise pendiente con el valor que pulse el usuario
export interface ActiveModal {
  kind: ModalKind;
  variant: AlertVariant;
  title: string;
  message: string;
  confirmText: string;
  cancelText: string;
  resolve: (value: boolean) => void;
}
