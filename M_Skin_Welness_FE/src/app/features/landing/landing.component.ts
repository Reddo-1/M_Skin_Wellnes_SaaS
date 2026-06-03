import { Component } from '@angular/core';
import { RouterLink } from '@angular/router';
import { IconComponent } from '../../shared/ui/icon/icon.component';

interface LandingFeature {
  icon: 'calendar' | 'shield-check' | 'users';
  title: string;
  description: string;
}

const FEATURES: LandingFeature[] = [
  {
    icon: 'calendar',
    title: 'Agenda y cuadrante',
    description: 'Organiza las citas por profesional y sala, e inícialas y finalízalas con un solo clic.',
  },
  {
    icon: 'shield-check',
    title: 'Consentimientos y ficha clínica',
    description: 'Firma digital RGPD, aptitud por tratamiento e historial clínico de cada paciente.',
  },
  {
    icon: 'users',
    title: 'Multi-rol y multi-centro',
    description: 'Recepción, profesionales, RRHH y administración, cada uno con su propio panel.',
  },
];

@Component({
  selector: 'app-landing',
  standalone: true,
  imports: [RouterLink, IconComponent],
  templateUrl: './landing.component.html',
})
export class LandingComponent {
  protected readonly features = FEATURES;
}
