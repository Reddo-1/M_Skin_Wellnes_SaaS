@extends('admin.layouts.app')

@section('title', 'Catálogos')
@section('page-title', 'Catálogos')
@section('page-subtitle', 'Valores comunes usados en todos los centros')

@section('content')
<div class="d-flex flex-column gap-3">

    @if (session('status'))
        <div class="sa-card p-3 sa-text-success" style="font-size: 13px;">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="sa-card p-3 sa-text-warn" style="font-size: 13px;">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="sa-card p-3" style="font-size: 12px;">
        <i class="bi bi-info-circle me-1"></i>
        Algunos nombres se usan internamente por el código (por ejemplo, los estados de sesión o tipos de movimiento de stock). Edítalos con criterio: renombrar un valor existente puede afectar al funcionamiento de la app.
    </div>

    <ul class="nav nav-tabs sa-lookup-tabs">
        @foreach ($catalogs as $key => $catalog)
            <li class="nav-item">
                <a class="nav-link {{ $active === $key ? 'active' : '' }}"
                   href="{{ route('admin.lookups.index', ['tab' => $key]) }}">
                    {{ $catalog['label'] }}
                </a>
            </li>
        @endforeach
    </ul>

    @php $current = $catalogs[$active]; @endphp

    <div class="sa-card p-3 p-md-4">
        <div class="sa-form-section mb-4">
            <div class="d-flex align-items-center justify-content-between mb-2 gap-2 flex-wrap">
                <div class="sa-form-section-title">
                    <i class="bi bi-plus-circle"></i> Añadir a «{{ $current['label'] }}»
                </div>
                <div class="sa-form-section-hint d-none d-md-block">
                    Se añadirá al final del catálogo seleccionado.
                </div>
            </div>
            <form method="POST" action="{{ route('admin.lookups.store', $active) }}" class="row g-2 align-items-start">
                @csrf
                <div class="col-md-{{ $current['has_sort_order'] ? 7 : 9 }}">
                    <input type="text" name="name" value="{{ old('name') }}"
                           class="form-control sa-input @error('name') is-invalid @enderror"
                           placeholder="Nombre del nuevo valor"
                           maxlength="60" required autofocus>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                @if ($current['has_sort_order'])
                    <div class="col-md-2">
                        <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}"
                               class="form-control sa-input @error('sort_order') is-invalid @enderror"
                               placeholder="Orden" min="0">
                    </div>
                @endif
                <div class="col-md-3">
                    <button type="submit" class="btn sa-btn-primary sa-btn-compact w-100">
                        <i class="bi bi-plus-lg"></i> Añadir
                    </button>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table sa-table mb-0 align-middle">
                <thead>
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th>Nombre</th>
                        @if ($current['has_sort_order'])
                            <th style="width: 120px;">Orden</th>
                        @endif
                        <th class="text-end" style="width: 110px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($current['items'] as $item)
                        <tr>
                            <td class="text-secondary" style="font-size: 12px;">{{ $item->id }}</td>
                            <td>
                                <form id="form-update-{{ $active }}-{{ $item->id }}"
                                      method="POST"
                                      action="{{ route('admin.lookups.update', [$active, $item->id]) }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="name" value="{{ $item->name }}"
                                           class="form-control sa-input" maxlength="60" required>
                                </form>
                            </td>
                            @if ($current['has_sort_order'])
                                <td>
                                    <input type="number" name="sort_order" value="{{ $item->sort_order }}"
                                           form="form-update-{{ $active }}-{{ $item->id }}"
                                           class="form-control sa-input" min="0">
                                </td>
                            @endif
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <button type="submit"
                                            form="form-update-{{ $active }}-{{ $item->id }}"
                                            class="btn sa-btn-primary sa-btn-icon" title="Guardar cambios">
                                        <i class="bi bi-check2"></i>
                                    </button>
                                    <form method="POST"
                                          action="{{ route('admin.lookups.destroy', [$active, $item->id]) }}"
                                          class="d-inline"
                                          onsubmit="return confirm('¿Borrar este registro? La operación falla si hay datos que lo estén usando.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn sa-btn-warn sa-btn-icon" title="Borrar">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $current['has_sort_order'] ? 4 : 3 }}"
                                class="text-center text-secondary py-4" style="font-size: 13px;">
                                Este catálogo está vacío.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
