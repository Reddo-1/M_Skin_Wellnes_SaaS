@extends('admin.layouts.app')

@section('title', 'Centros')
@section('page-title', 'Centros')
@section('page-subtitle', 'Listado global de centros del SaaS')

@php
    $planClass = ['starter' => 'starter', 'professional' => 'pro', 'premium' => 'premium'];
@endphp

@section('content')
<div class="d-flex flex-column gap-3">

    @if (session('status'))
        <div class="sa-card p-3 sa-text-success" style="font-size: 13px;">
            {{ session('status') }}
        </div>
    @endif

    <form method="GET" action="{{ route('admin.centers.index') }}" class="sa-card p-3" id="centersFilters">
        <div class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label sa-input-label">Buscar</label>
                <div class="sa-input-group">
                    <i class="bi bi-search sa-input-icon"></i>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                           class="form-control sa-input sa-input-with-icon"
                           placeholder="Nombre o identificador del centro"
                           autocomplete="off">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label sa-input-label">Plan</label>
                <select name="plan_id" class="form-select sa-input">
                    <option value="">Todos los planes</option>
                    @foreach ($plans as $plan)
                        <option value="{{ $plan->id }}" @selected(($filters['plan_id'] ?? '') == $plan->id)>
                            {{ $plan->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label sa-input-label">Estado</label>
                <div class="d-flex gap-2">
                    <select name="status" class="form-select sa-input flex-grow-1">
                        <option value="">Todos</option>
                        <option value="active" @selected(($filters['status'] ?? '') === 'active')>Activos</option>
                        <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Desactivados</option>
                    </select>
                    <a href="{{ route('admin.centers.index') }}" class="sa-reset-btn" title="Limpiar filtros">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </div>
        </div>
    </form>

    <div class="sa-card overflow-hidden">
        <div class="table-responsive">
            <table class="table sa-table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Centro</th>
                        <th>Plan</th>
                        <th class="text-center">Usuarios</th>
                        <th class="text-center">Estado</th>
                        <th>Creado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($centers as $i => $center)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="sa-center-logo c{{ ($i % 5) + 1 }}">
                                        {{ \Illuminate\Support\Str::of($center->name)->substr(0, 2)->upper() }}
                                    </div>
                                    <div style="min-width: 0;">
                                        <div class="fw-medium text-truncate" style="font-size: 13px;">{{ $center->name }}</div>
                                        <div class="text-secondary" style="font-size: 11px;">{{ $center->slug }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="sa-plan-pill {{ $planClass[$center->plan?->code] ?? 'starter' }}">
                                    {{ $center->plan?->name ?? 'Sin plan' }}
                                </span>
                            </td>
                            <td class="text-center" style="font-size: 13px;">{{ $center->users_count }}</td>
                            <td class="text-center">
                                @if ($center->is_active)
                                    <span class="sa-status-badge is-active">Activo</span>
                                @else
                                    <span class="sa-status-badge is-inactive">Inactivo</span>
                                @endif
                            </td>
                            <td class="text-secondary" style="font-size: 12px;">
                                {{ $center->created_at->locale('es')->isoFormat('D MMM YYYY') }}
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.centers.show', $center) }}" class="btn sa-btn-ghost sa-btn-icon" title="Ver ficha">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-4" style="font-size: 13px;">
                                No hay centros que coincidan con los filtros.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($centers->hasPages())
            <div class="border-top sa-activity-border px-3 py-2">
                {{ $centers->links() }}
            </div>
        @endif
    </div>

</div>

@push('scripts')
<script>
    (function () {
        const form = document.getElementById('centersFilters');
        if (!form) return;

        const submit = () => form.submit();
        let timer;

        form.querySelectorAll('select').forEach(elemento => elemento.addEventListener('change', submit));

        const search = form.querySelector('input[name="search"]');
        if (search) {
            search.addEventListener('input', () => {
                clearTimeout(timer);
                timer = setTimeout(submit, 350);
            });
        }
    })();
</script>
@endpush
@endsection
