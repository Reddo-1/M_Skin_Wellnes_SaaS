import { Component, HostListener, effect, input, output } from '@angular/core';

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
    effect(() => {
      document.body.style.overflow = this.isOpen() ? 'hidden' : '';
    });
  }

  protected onBackdropClick(): void {
    if (!this.isFullscreen()) {
      this.close.emit();
    }
  }

  @HostListener('document:keydown.escape')
  protected onEscape(): void {
    if (this.isOpen()) {
      this.close.emit();
    }
  }
}
