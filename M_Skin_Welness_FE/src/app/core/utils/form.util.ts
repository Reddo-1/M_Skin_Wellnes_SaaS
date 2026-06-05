import { HttpErrorResponse } from '@angular/common/http';
import { AbstractControl } from '@angular/forms';

export const GENERIC_ERROR = 'Ha ocurrido un error. Inténtalo de nuevo en unos segundos.';

export function loadResourceError(resource: string): string {
  return `No se han podido cargar ${resource}. Vuelve a intentarlo en unos segundos.`;
}

export function apiError(error: unknown): string {
  if (error instanceof HttpErrorResponse) {
    const message = error.error?.message;
    if (typeof message === 'string' && message.trim() !== '') {
      return message;
    }
  }
  return GENERIC_ERROR;
}

export function hasFieldError(control: AbstractControl): boolean {
  return control.touched && control.invalid;
}

export function hasValidationError(control: AbstractControl, key: string): boolean {
  return control.touched && control.hasError(key);
}
