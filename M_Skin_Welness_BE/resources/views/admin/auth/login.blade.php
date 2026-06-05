<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Acceso · MSkinWellness</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon-96x96.png') }}" sizes="96x96">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    @vite(['resources/css/app.css', 'resources/css/admin/login.css', 'resources/js/app.js'])
</head>
<body>

<div class="bg-grid-overlay"></div>
<div class="bg-glow-1"></div>
<div class="bg-glow-2"></div>

<div class="stage container-fluid">
    <div class="row g-0 h-100 align-items-center">

        <div class="col-lg-6 d-none d-lg-flex flex-column justify-content-between p-5" style="height: 100%;">
            <div>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="logo-mark">MS</div>
                    <div>
                        <div class="fw-semibold" style="font-size: 22px;">MSkinWellness</div>
                        <div class="eyebrow mt-1">Plataforma SaaS</div>
                    </div>
                </div>
                <span class="status-pill">
                    <span class="status-dot"></span>
                    Todos los sistemas operativos
                </span>
            </div>

            <div>
                <h1 class="fw-semibold mb-3" style="font-size: clamp(2.35rem, 3.4vw, 3rem); line-height: 1.2;">
                    Panel de <span class="text-purple">superadministración</span>
                </h1>
                <p class="text-secondary mb-4" style="font-size: 18px; max-width: 500px;">
                    Control global de centros, planes y métricas de la plataforma. Acceso reservado al equipo de operaciones.
                </p>
                <div class="stat-row">
                    <div>
                        <span class="num">{{ $centersCount }}</span>
                        {{ $centersCount === 1 ? 'centro activo' : 'centros activos' }}
                    </div>
                    <div>
                        <span class="num">{{ number_format($usersCount, 0, ',', '.') }}</span>
                        {{ $usersCount === 1 ? 'usuario registrado' : 'usuarios registrados' }}
                    </div>
                    <div>
                        <span class="num">{{ $plansCount }}</span>
                        {{ $plansCount === 1 ? 'plan disponible' : 'planes disponibles' }}
                    </div>
                </div>
            </div>

            <div></div>
        </div>

        <div class="col-12 col-lg-6 d-flex justify-content-center align-items-center p-3 p-md-4 login-form">
            <div class="login-card">

                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="logo-mark sm">MS</div>
                        <div>
                            <div class="fw-semibold" style="font-size: 20px;">MSkinWellness</div>
                            <div class="eyebrow mt-1">Superadministración</div>
                        </div>
                    </div>
                    <span class="status-pill sm d-none d-sm-inline-flex">
                        <span class="status-dot"></span>
                        Online
                    </span>
                </div>

                <h2 class="fw-semibold mb-1" style="font-size: 2.05rem;">Inicia sesión</h2>
                <p class="text-secondary mb-4" style="font-size: 17px;">
                    Acceso restringido al panel de <span class="text-purple">superadministración</span>.
                </p>

                @if ($errors->any())
                    <div class="alert alert-danger py-2 small mb-3">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.login.submit') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label">Correo electrónico</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}"
                               class="form-control" placeholder="admin@mskinwellness.com" required autofocus>
                    </div>

                    <div class="mb-2">
                        <label for="password" class="form-label">Contraseña</label>
                        <div class="password-wrap">
                            <input id="password" type="password" name="password"
                                   class="form-control pe-5" placeholder="••••••••" required>
                            <button type="button" class="eye" id="togglePassword" aria-label="Mostrar contraseña">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-check mb-4 mt-3">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label text-secondary" for="remember" style="font-size: 14px;">
                            Recordar sesión
                        </label>
                    </div>

                    <button type="submit" class="btn btn-purple w-100">Acceder al panel</button>
                </form>

            </div>
        </div>

    </div>
</div>

<script>
    document.getElementById('togglePassword').addEventListener('click', function () {
        const input = document.getElementById('password');
        const icon = this.querySelector('i');
        const isHidden = input.type === 'password';

        input.type = isHidden ? 'text' : 'password';
        icon.classList.toggle('bi-eye', !isHidden);
        icon.classList.toggle('bi-eye-slash', isHidden);
        this.setAttribute('aria-label', isHidden ? 'Ocultar contraseña' : 'Mostrar contraseña');
    });
</script>

</body>
</html>
