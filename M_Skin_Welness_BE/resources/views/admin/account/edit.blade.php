@extends('admin.layouts.app')

@section('title', 'Mi cuenta')
@section('page-title', 'Mi cuenta')
@section('page-subtitle', 'Tus datos como superadministrador')

@section('content')
<div class="row g-3">
    <div class="col-lg-7">
        <div class="sa-card p-3 p-md-4">

            @if (session('status'))
                <div class="mb-3 sa-text-success" style="font-size: 13px;">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.account.update') }}">
                @csrf
                @method('PUT')

                <div class="sa-card-title mb-3">Datos personales</div>

                <div class="mb-3">
                    <label class="form-label text-secondary" style="font-size: 12px;">Nombre</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}"
                           class="form-control sa-input @error('name') is-invalid @enderror"
                           maxlength="120" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="form-label text-secondary" style="font-size: 12px;">Correo</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}"
                           class="form-control sa-input @error('email') is-invalid @enderror"
                           maxlength="255" required>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="sa-card-title mb-3">Cambiar contraseña</div>
                <div class="text-secondary mb-3" style="font-size: 12px;">
                    Déjalo en blanco si no quieres cambiarla.
                </div>

                <div class="mb-3">
                    <label class="form-label text-secondary" style="font-size: 12px;">Contraseña actual</label>
                    <input type="password" name="current_password" autocomplete="current-password"
                           class="form-control sa-input @error('current_password') is-invalid @enderror">
                    @error('current_password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label text-secondary" style="font-size: 12px;">Nueva contraseña</label>
                    <input type="password" name="password" autocomplete="new-password"
                           class="form-control sa-input @error('password') is-invalid @enderror">
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="form-label text-secondary" style="font-size: 12px;">Confirmar nueva contraseña</label>
                    <input type="password" name="password_confirmation" autocomplete="new-password"
                           class="form-control sa-input">
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn sa-btn-primary">
                        <i class="bi bi-check2 me-1"></i> Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
