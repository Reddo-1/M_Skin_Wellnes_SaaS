import { DatePipe } from '@angular/common';
import { Component, input } from '@angular/core';
import { User } from '../../../../../core/models/user.model';

@Component({
  selector: 'app-personal-tab',
  standalone: true,
  imports: [DatePipe],
  templateUrl: './personal-tab.component.html',
})
export class PersonalTabComponent {
  readonly client = input.required<User>();
}
