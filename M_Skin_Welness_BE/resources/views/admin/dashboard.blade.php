@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Mi panel')
@section('page-subtitle', 'Panel de superadministración · MSkinWellness')

@push('head')
    @vite(['resources/js/admin/dashboard.js'])
@endpush

@php
    $activityIconColor = [
        'center.created' => 'var(--sa-purple)',
        'user.created' => 'var(--sa-info)',
        'center.deactivated' => 'var(--sa-warn)',
    ];

    $activityLabel = [
        'center.created' => 'Nuevo centro dado de alta',
        'user.created' => 'Nuevo usuario registrado',
        'center.deactivated' => 'Centro desactivado',
    ];

    $planSwatchColor = ['starter' => '#6B6C74', 'professional' => '#5B9EEF', 'premium' => '#A78BFA'];
@endphp

@section('content')
<div class="row g-3">

    <div class="col-lg-8 d-flex flex-column gap-3">

        <div class="row g-3">
            <div class="col-md-4">
                <div class="sa-card p-3 h-100">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="sa-metric-icon purple"><i class="bi bi-building"></i></div>
                        <div class="fw-medium" style="font-size: 13px;">Centros activos</div>
                    </div>
                    <div class="sa-metric-value mb-2">{{ $metrics['centers']['value'] }}</div>
                    <div class="text-secondary" style="font-size: 12px;">
                        @if ($metrics['centers']['newThisMonth'] > 0)
                            <span class="sa-text-success fw-medium">+ {{ $metrics['centers']['newThisMonth'] }}</span> este mes
                        @else
                            Sin altas este mes
                        @endif
                        @if ($metrics['centers']['deactivated'] > 0)
                            · {{ $metrics['centers']['deactivated'] }} desactivados
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="sa-card p-3 h-100">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="sa-metric-icon blue"><i class="bi bi-people-fill"></i></div>
                        <div class="fw-medium" style="font-size: 13px;">Usuarios totales</div>
                    </div>
                    <div class="sa-metric-value mb-2">{{ number_format($metrics['users']['value'], 0, ',', '.') }}</div>
                    <div class="text-secondary" style="font-size: 12px;">
                        @if ($metrics['users']['changePct'] !== null)
                            <span class="{{ $metrics['users']['changePct'] >= 0 ? 'sa-text-success' : 'sa-text-warn' }} fw-medium">
                                {{ $metrics['users']['changePct'] >= 0 ? '+' : '' }}{{ $metrics['users']['changePct'] }} %
                            </span>
                            vs mes anterior
                        @else
                            Sin histórico previo
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="sa-card p-3 h-100">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="sa-metric-icon teal"><i class="bi bi-currency-euro"></i></div>
                        <div class="fw-medium" style="font-size: 13px;">Ingreso mensual</div>
                    </div>
                    <div class="sa-metric-value mb-2">{{ number_format($metrics['monthly_revenue']['value'], 0, ',', '.') }} €</div>
                    <div class="text-secondary" style="font-size: 12px;">
                        @if ($metrics['monthly_revenue']['changePct'] !== null)
                            <span class="{{ $metrics['monthly_revenue']['changePct'] >= 0 ? 'sa-text-success' : 'sa-text-warn' }} fw-medium">
                                {{ $metrics['monthly_revenue']['changePct'] >= 0 ? '+' : '' }}{{ $metrics['monthly_revenue']['changePct'] }} %
                            </span>
                            vs mes anterior
                        @else
                            Sin histórico previo
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-12">
                <div class="sa-card p-3 h-100">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="sa-metric-icon blue"><i class="bi bi-lightning-charge"></i></div>
                        <div class="fw-medium" style="font-size: 13px;">Usuarios conectados</div>
                    </div>
                    <div class="sa-metric-value mb-2">{{ $metrics['online_users'] }}</div>
                    <div class="text-secondary" style="font-size: 12px;">
                        Actividad en los últimos 15 minutos
                    </div>
                </div>
            </div>
        </div>

        <div class="sa-card p-3 p-md-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="sa-card-title">Crecimiento de centros ultimos 12 meses</div>
            </div>
            <div style="height: 200px;">
                <canvas id="growthChart"
                        data-labels='@json(array_column($growth, "label"))'
                        data-values='@json(array_column($growth, "value"))'></canvas>
            </div>
        </div>

    </div>

    <div class="col-lg-4 d-flex flex-column gap-3">

        <div class="sa-card p-3 p-md-4">
            <div class="sa-card-title mb-3" style="font-size: 14px;">Distribución por plan</div>

            @foreach ($planDistribution as $plan)
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="d-flex align-items-center gap-2 text-secondary" style="font-size: 12px;">
                        <span class="sa-plan-swatch" style="background: {{ $planSwatchColor[$plan['code']] ?? '#6B6C74' }};"></span>
                        {{ $plan['name'] }}
                    </div>
                    <div class="fw-semibold" style="font-size: 13px;">{{ $plan['count'] }}</div>
                </div>
                <div class="sa-plan-bar {{ !$loop->last ? 'mb-3' : '' }}">
                    <div style="width: {{ $plan['percent'] }}%; background: {{ $planSwatchColor[$plan['code']] ?? '#6B6C74' }};"></div>
                </div>
            @endforeach
        </div>

        <div class="sa-card p-3 p-md-4">
            <div class="sa-card-title mb-3" style="font-size: 14px;">Actividad reciente</div>

            @forelse ($recentActivity as $i => $event)
                <div class="d-flex gap-2 {{ $i === 0 ? 'pb-3' : (count($recentActivity) - 1 === $i ? 'pt-3' : 'py-3') }} {{ !$loop->last ? 'border-bottom sa-activity-border' : '' }}">
                    <span class="sa-activity-dot" style="background: {{ $activityIconColor[$event['action']] ?? 'var(--sa-purple)' }};"></span>
                    <div class="sa-activity-text">
                        {{ $activityLabel[$event['action']] ?? $event['action'] }}
                        @if ($event['center'])
                            · <b>{{ $event['center'] }}</b>
                        @endif
                        <div class="sa-activity-time">{{ $event['when'] }}</div>
                    </div>
                </div>
            @empty
                <div class="text-center text-secondary py-2" style="font-size: 12px;">
                    No hay actividad reciente.
                </div>
            @endforelse
        </div>

    </div>

</div>
@endsection
