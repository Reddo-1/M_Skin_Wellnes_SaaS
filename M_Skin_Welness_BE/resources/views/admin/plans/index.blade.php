@extends('admin.layouts.app')

@section('title', 'Planes')
@section('page-title', 'Planes')
@section('page-subtitle', 'Planes comerciales del SaaS — los datos viven en el seeder')

@php
    $planClass = ['starter' => 'starter', 'professional' => 'pro', 'premium' => 'premium'];

    $featureLabel = [
        'allows_online_clients' => 'Acceso online del cliente',
        'allows_emails' => 'Correos automáticos',
        'allows_public_page' => 'Página pública del centro',
        'allows_custom_domain' => 'Dominio personalizado',
    ];
@endphp

@section('content')
<div class="row g-3">
    @foreach ($plans as $plan)
        <div class="col-md-4">
            <div class="sa-card p-3 p-md-4 h-100 d-flex flex-column">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="sa-plan-pill {{ $planClass[$plan->code] ?? 'starter' }}">{{ $plan->name }}</span>
                    @if (! $plan->is_active)
                        <span class="sa-text-warn fw-medium" style="font-size: 11px;">Inactivo</span>
                    @endif
                </div>

                <div class="sa-metric-value mb-1">
                    {{ number_format($plan->monthly_price, 0, ',', '.') }} €
                    <span class="text-secondary" style="font-size: 12px; font-weight: normal;">/mes</span>
                </div>

                <div class="text-secondary mb-3" style="font-size: 12px; min-height: 36px;">
                    {{ $plan->description }}
                </div>

                <dl class="row mb-3" style="font-size: 13px;">
                    <dt class="col-sm-7 text-secondary fw-normal">Trabajadores máx.</dt>
                    <dd class="col-sm-5 text-end">{{ $plan->max_workers }}</dd>

                    <dt class="col-sm-7 text-secondary fw-normal">Centros con este plan</dt>
                    <dd class="col-sm-5 text-end">{{ $plan->centers_count }}</dd>
                </dl>

                <div class="mb-3 flex-grow-1">
                    @foreach ($featureLabel as $field => $label)
                        <div class="d-flex align-items-center gap-2 mb-1" style="font-size: 12px;">
                            @if ($plan->$field)
                                <i class="bi bi-check-circle-fill sa-text-success"></i>
                            @else
                                <i class="bi bi-x-circle text-secondary"></i>
                            @endif
                            <span class="{{ $plan->$field ? '' : 'text-secondary' }}">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="border-top sa-activity-border pt-2" style="font-size: 11px;">
                    <div class="text-secondary mb-1">Identificador Stripe</div>
                    <code style="background: transparent; color: inherit;">{{ $plan->stripe_price_id ?? '—' }}</code>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="mt-3 text-secondary" style="font-size: 11px;">
    Para añadir, modificar o desactivar planes, edita <code style="background: transparent; color: inherit;">database/seeders/PlanSeeder.php</code> y vuelve a ejecutar los seeders.
</div>
@endsection
