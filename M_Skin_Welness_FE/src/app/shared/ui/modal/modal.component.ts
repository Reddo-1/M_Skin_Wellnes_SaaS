import { Component, effect, input, output } from '@angular/core';

@Component({
  selector: 'app-modal',
  standalone: true,
  templateUrl: './modal.component.html',
})
export class ModalComponent {
  readonly isOpen = input.required<boolean>();
  readonly showCloseButton = input<boolean>(true);
  readonly isFullscreen = input<boolean>(false);
  readonly panelClass = input<string>('');

  readonly close = output<void>();

  constructor() {
    effect((onCleanup) => {
      document.body.style.overflow = this.isOpen() ? 'hidden' : '';
      onCleanup(() => {
        document.body.style.overflow = '';
      });
    });

    effect((onCleanup) => {
      if (!this.isOpen()) return;
      const closeOnEscape = (event: KeyboardEvent) => {
        if (event.key === 'Escape') this.close.emit();
      };
      document.addEventListener('keydown', closeOnEscape);
      onCleanup(() => document.removeEventListener('keydown', closeOnEscape));
    });
  }

  protected onBackdropClick(): void {
    if (!this.isFullscreen()) {
      this.close.emit();
    }
  }
}
