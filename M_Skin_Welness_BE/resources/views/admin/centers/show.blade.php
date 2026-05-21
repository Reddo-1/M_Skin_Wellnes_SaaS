@extends('admin.layouts.app')

@section('title', $center->name)
@section('page-title', $center->name)
@section('page-subtitle', 'Ficha del centro')

@php
    $planClass = ['starter' => 'starter', 'professional' => 'pro', 'premium' => 'premium'];

    $activityLabel = [
        'center.created' => 'Centro creado',
        'center.plan_changed' => 'Plan modificado',
        'center.deactivated' => 'Centro desactivado',
        'center.activated' => 'Centro reactivado',
        'user.created' => 'Nuevo usuario registrado',
    ];

    $subscriptionLabel = [
        'active' => 'Activa',
        'trialing' => 'En periodo de prueba',
        'past_due' => 'Pago pendiente',
        'unpaid' => 'Sin pagar',
        'canceled' => 'Cancelada',
        'incomplete' => 'Pago incompleto',
        'incomplete_expired' => 'Pago caducado',
        'paused' => 'En pausa',
    ];

    $subscriptionTone = [
        'active' => 'sa-text-success',
        'trialing' => 'sa-text-success',
        'past_due' => 'sa-text-warn',
        'unpaid' => 'sa-text-warn',
        'canceled' => 'sa-text-warn',
        'incomplete' => 'sa-text-warn',
        'incomplete_expired' => 'sa-text-warn',
        'paused' => 'sa-text-warn',
    ];
@endphp

@section('content')
<div class="row g-3">

    <div class="col-lg-7 d-flex flex-column gap-3">

        <div class="sa-card p-3 p-md-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="sa-card-title">Datos del centro</div>
                <form method="POST" action="{{ route('admin.centers.impersonate', $center) }}" target="_blank">
                    @csrf
                    <button type="submit" class="btn btn-sm sa-btn-primary"
                            title="Abre el panel del centro en una pestaña nueva con tu sesión de superadmin">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Entrar como admin
                    </button>
                </form>
            </div>

            <dl class="row mb-0" style="font-size: 13px;">
                <dt class="col-sm-4 text-secondary fw-normal">Identificador</dt>
                <dd class="col-sm-8">{{ $center->slug }}</dd>

                <dt class="col-sm-4 text-secondary fw-normal">UUID</dt>
                <dd class="col-sm-8 text-secondary" style="font-size: 11px;">{{ $center->uuid }}</dd>

                <dt class="col-sm-4 text-secondary fw-normal">Plan</dt>
                <dd class="col-sm-8">
                    <span class="sa-plan-pill {{ $planClass[$center->plan?->code] ?? 'starter' }}">
                        {{ $center->plan?->name ?? 'Sin plan' }}
                    </span>
                </dd>

                <dt class="col-sm-4 text-secondary fw-normal">Estado</dt>
                <dd class="col-sm-8">
                    @if ($center->is_active)
                        <span class="sa-text-success fw-medium">Activo</span>
                    @else
                        <span class="sa-text-warn fw-medium">Desactivado</span>
                    @endif
                </dd>

                <dt class="col-sm-4 text-secondary fw-normal">Dominio personalizado</dt>
                <dd class="col-sm-8">{{ $center->custom_domain ?? '—' }}</dd>

                <dt class="col-sm-4 text-secondary fw-normal">Usuarios</dt>
                <dd class="col-sm-8">{{ $center->users_count }}</dd>

                <dt class="col-sm-4 text-secondary fw-normal">Creado</dt>
                <dd class="col-sm-8 text-secondary">
                    {{ $center->created_at->locale('es')->isoFormat('D MMM YYYY, HH:mm') }}
                </dd>
            </dl>
        </div>

        <div class="sa-card p-3 p-md-4">
            <div class="sa-card-title mb-3">Suscripción</div>

            @if ($subscription === null)
                <div class="text-secondary" style="font-size: 13px;">
                    Este centro no tiene una suscripción activa registrada.
                </div>
            @else
                <dl class="row mb-0" style="font-size: 13px;">
                    <dt class="col-sm-4 text-secondary fw-normal">Estado</dt>
                    <dd class="col-sm-8">
                        <span class="{{ $subscriptionTone[$subscription['status']] ?? '' }} fw-medium">
                            {{ $subscriptionLabel[$subscription['status']] ?? $subscription['status'] }}
                        </span>
                        @if ($subscription['cancel_at_period_end'])
                            <span class="text-secondary" style="font-size: 11px;">· se cancelará al cierre del periodo</span>
                        @elseif ($subscription['on_grace_period'])
                            <span class="text-secondary" style="font-size: 11px;">· en periodo de gracia</span>
                        @endif
                    </dd>

                    <dt class="col-sm-4 text-secondary fw-normal">Identificador Stripe</dt>
                    <dd class="col-sm-8 text-secondary" style="font-size: 11px;">{{ $subscription['stripe_id'] }}</dd>

                    @if ($subscription['on_trial'] && $subscription['trial_ends_at'])
                        <dt class="col-sm-4 text-secondary fw-normal">Fin del periodo de prueba</dt>
                        <dd class="col-sm-8 text-secondary">
                            {{ $subscription['trial_ends_at']->locale('es')->isoFormat('D MMM YYYY') }}
                        </dd>
                    @endif

                    <dt class="col-sm-4 text-secondary fw-normal">Próxima renovación</dt>
                    <dd class="col-sm-8 text-secondary">
                        @if ($subscription['current_period_end'])
                            {{ $subscription['current_period_end']->locale('es')->isoFormat('D MMM YYYY') }}
                        @elseif (! $subscription['live_data_available'])
                            <span class="sa-text-warn">No se pudo consultar Stripe ahora mismo</span>
                        @else
                            —
                        @endif
                    </dd>

                    @if ($subscription['ends_at'])
                        <dt class="col-sm-4 text-secondary fw-normal">Fin de suscripción</dt>
                        <dd class="col-sm-8 text-secondary">
                            {{ $subscription['ends_at']->locale('es')->isoFormat('D MMM YYYY') }}
                        </dd>
                    @endif

                    @if ($subscription['card'])
                        <dt class="col-sm-4 text-secondary fw-normal">Tarjeta</dt>
                        <dd class="col-sm-8 text-secondary">
                            {{ ucfirst($subscription['card']['brand']) }} ···· {{ $subscription['card']['last_four'] }}
                        </dd>
                    @endif

                    @if ($center->billingUser)
                        <dt class="col-sm-4 text-secondary fw-normal">Responsable</dt>
                        <dd class="col-sm-8">
                            {{ $center->billingUser->name }}
                            <span class="text-secondary" style="font-size: 11px;">· {{ $center->billingUser->email }}</span>
                        </dd>
                    @endif
                </dl>
            @endif
        </div>

    </div>

    <div class="col-lg-5">
        <div class="sa-card p-3 p-md-4">
            <div class="sa-card-title mb-3" style="font-size: 14px;">Actividad del centro</div>

            @forelse ($recentActivity as $i => $event)
                <div class="d-flex gap-2 {{ $i === 0 ? 'pb-3' : (count($recentActivity) - 1 === $i ? 'pt-3' : 'py-3') }} {{ !$loop->last ? 'border-bottom sa-activity-border' : '' }}">
                    <span class="sa-activity-dot" style="background: var(--sa-purple);"></span>
                    <div class="sa-activity-text">
                        {{ $activityLabel[$event->action] ?? $event->action }}
                        @if ($event->actor)
                            · <b>{{ $event->actor->name }}</b>
                        @endif
                        <div class="sa-activity-time">{{ $event->created_at->locale('es')->diffForHumans() }}</div>
                    </div>
                </div>
            @empty
                <div class="text-center text-secondary py-2" style="font-size: 12px;">
                    Sin actividad registrada.
                </div>
            @endforelse
        </div>
    </div>

</div>

<div class="mt-3">
    <a href="{{ route('admin.centers.index') }}" class="sa-back-link">
        <i class="bi bi-arrow-left"></i> Volver al listado
    </a>
</div>
@endsection
