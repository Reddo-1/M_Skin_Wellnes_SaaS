import { Pipe, PipeTransform } from '@angular/core';

@Pipe({
  name: 'normalize',
  standalone: true,
})
export class NormalizePipe implements PipeTransform {
  transform(value: string | null | undefined): string {
    if (!value) return '—';
    const text = value.replace(/[_-]/g, ' ').trim();
    return text.charAt(0).toUpperCase() + text.slice(1);
  }
}
