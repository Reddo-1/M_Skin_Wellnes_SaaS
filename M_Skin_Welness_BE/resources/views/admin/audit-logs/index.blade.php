@extends('admin.layouts.app')

@section('title', 'Auditoría')
@section('page-title', 'Auditoría')
@section('page-subtitle', 'Registro de eventos del ciclo de vida de los centros')

@php
    $actionLabel = [
        'center.created' => 'Centro creado',
        'center.plan_changed' => 'Plan modificado',
        'center.deactivated' => 'Centro desactivado',
        'center.activated' => 'Centro reactivado',
        'user.created' => 'Nuevo usuario registrado',
    ];

    $actionTone = [
        'center.created' => 'sa-text-success',
        'center.plan_changed' => '',
        'center.deactivated' => 'sa-text-warn',
        'center.activated' => 'sa-text-success',
        'user.created' => '',
    ];
@endphp

@section('content')
<div class="d-flex flex-column gap-3">

    <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="sa-card p-3" id="auditFilters">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label sa-input-label">Centro</label>
                <select name="center_id" class="form-select sa-input">
                    <option value="">Todos los centros</option>
                    @foreach ($centers as $center)
                        <option value="{{ $center->id }}" @selected(($filters['center_id'] ?? '') == $center->id)>
                            {{ $center->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label sa-input-label">Acción</label>
                <select name="action" class="form-select sa-input">
                    <option value="">Todas las acciones</option>
                    @foreach ($actions as $action)
                        <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>
                            {{ $actionLabel[$action] ?? $action }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label sa-input-label">Desde</label>
                <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control sa-input">
            </div>
            <div class="col-md-2">
                <label class="form-label sa-input-label">Hasta</label>
                <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control sa-input">
            </div>
            <div class="col-md-2">
                <label class="form-label sa-input-label">Reiniciar Filtros</label>
                <a href="{{ route('admin.audit-logs.index') }}" class="btn sa-btn-ghost w-100 sa-input d-flex align-items-center justify-content-center" title="Limpiar filtros">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </div>
    </form>

    <div class="sa-card overflow-hidden">
        <div class="table-responsive">
            <table class="table sa-table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Cuándo</th>
                        <th>Acción</th>
                        <th>Centro</th>
                        <th>Actor</th>
                        <th>Detalles</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td class="text-secondary" style="font-size: 12px;">
                                <div>{{ $log->created_at->locale('es')->isoFormat('D MMM YYYY') }}</div>
                                <div style="font-size: 11px;">{{ $log->created_at->locale('es')->isoFormat('HH:mm') }}</div>
                            </td>
                            <td>
                                <span class="{{ $actionTone[$log->action] ?? '' }} fw-medium" style="font-size: 13px;">
                                    {{ $actionLabel[$log->action] ?? $log->action }}
                                </span>
                            </td>
                            <td style="font-size: 13px;">
                                @if ($log->center)
                                    <a href="{{ route('admin.centers.show', $log->center_id) }}" class="sa-back-link" style="font-size: 13px;">
                                        {{ $log->center->name }}
                                    </a>
                                @else
                                    <span class="text-secondary">—</span>
                                @endif
                            </td>
                            <td style="font-size: 13px;">
                                @if ($log->actor)
                                    <div>{{ $log->actor->name }}</div>
                                    <div class="text-secondary" style="font-size: 11px;">{{ $log->actor->email }}</div>
                                @else
                                    <span class="text-secondary">Sistema</span>
                                @endif
                            </td>
                            <td class="text-secondary" style="font-size: 11px;">
                                @if (! empty($log->metadata))
                                    <code style="background: transparent; color: inherit;">{{ json_encode($log->metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</code>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-4" style="font-size: 13px;">
                                No hay eventos que coincidan con los filtros.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($logs->hasPages())
            <div class="border-top sa-activity-border px-3 py-2">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

</div>

@push('scripts')
<script>
    (function () {
        const form = document.getElementById('auditFilters');
        if (!form) return;

        const submit = () => form.submit();
        form.querySelectorAll('select, input[type="date"]').forEach(elemento => elemento.addEventListener('change', submit));
    })();
</script>
@endpush
@endsection
