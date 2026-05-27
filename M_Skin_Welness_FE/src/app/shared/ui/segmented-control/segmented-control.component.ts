import { Component, input, output } from '@angular/core';

export interface SegmentedControlOption<T extends string> {
  value: T;
  label: string;
}

@Component({
  selector: 'app-segmented-control',
  standalone: true,
  templateUrl: './segmented-control.component.html',
})
export class SegmentedControlComponent<T extends string> {
  readonly options = input.required<SegmentedControlOption<T>[]>();
  readonly selected = input.required<T>();

  readonly selectionChange = output<T>();

  protected onSelect(value: T): void {
    if (value !== this.selected()) {
      this.selectionChange.emit(value);
    }
  }
}
