import { Component, input, output } from '@angular/core';

export interface RadioOption<T> {
  value: T;
  label: string;
  description?: string;
}

let nextGroupId = 0;

@Component({
  selector: 'app-radio-group',
  standalone: true,
  templateUrl: './radio-group.component.html',
})
export class RadioGroupComponent<T> {
  readonly options = input.required<RadioOption<T>[]>();
  readonly selected = input<T | null>(null);
  readonly invalid = input(false);
  readonly inline = input(true);

  readonly selectionChange = output<T>();

  protected readonly groupName = `app-radio-group-${nextGroupId++}`;

  protected isSelected(value: T): boolean {
    return this.selected() === value;
  }

  protected onSelect(value: T): void {
    this.selectionChange.emit(value);
  }
}
