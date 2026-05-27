import { Pipe, PipeTransform } from '@angular/core';

@Pipe({
  name: 'boolean',
  standalone: true,
})
export class BooleanPipe implements PipeTransform {
  transform(value: boolean | null | undefined): string {
    if (value === true) return 'Sí';
    if (value === false) return 'No';
    return '—';
  }
}
