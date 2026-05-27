import { AbstractControl } from '@angular/forms';

//fallback generico cuando el back no manda body o el catch no pone un fallback propio
export const GENERIC_ERROR = 'Ha ocurrido un error. Inténtalo de nuevo en unos segundos.';

export function loadResourceError(resource: string): string {
  return `No se han podido cargar ${resource}. Vuelve a intentarlo en unos segundos.`;
}

export function hasFieldError(control: AbstractControl): boolean {
  return control.touched && control.invalid;
}

export function hasValidationError(control: AbstractControl, key: string): boolean {
  return control.touched && control.hasError(key);
}
