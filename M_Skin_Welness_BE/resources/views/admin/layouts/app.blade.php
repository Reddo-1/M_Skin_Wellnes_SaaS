<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel') · MSkinWellness</title>
    @vite(['resources/css/app.css', 'resources/css/admin/dashboard.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 p-2 p-md-3">

<div class="sa-app rounded-4 w-100 d-flex">

    <aside class="sa-sidebar d-flex flex-column align-items-center py-3 gap-1">
        <div class="sa-brand mb-3">MS</div>

        <a href="{{ route('admin.dashboard') }}"
           class="sa-nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
           title="Dashboard">
            <i class="bi bi-grid-1x2-fill"></i>
        </a>
        <a href="{{ route('admin.centers.index') }}"
           class="sa-nav-item {{ request()->routeIs('admin.centers.*') ? 'active' : '' }}"
           title="Centros">
            <i class="bi bi-building"></i>
        </a>
        <a href="{{ route('admin.plans.index') }}"
           class="sa-nav-item {{ request()->routeIs('admin.plans.*') ? 'active' : '' }}"
           title="Planes">
            <i class="bi bi-credit-card-2-front"></i>
        </a>
        <a href="{{ route('admin.audit-logs.index') }}"
           class="sa-nav-item {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}"
           title="Auditoría">
            <i class="bi bi-clock-history"></i>
        </a>
        <a href="{{ route('admin.lookups.index') }}"
           class="sa-nav-item {{ request()->routeIs('admin.lookups.*') ? 'active' : '' }}"
           title="Catálogos">
            <i class="bi bi-list-ul"></i>
        </a>
    </aside>

    <main class="flex-grow-1 d-flex flex-column" style="min-width: 0;">

        <header class="sa-topbar d-flex align-items-center justify-content-between px-3 px-md-4 gap-3">
            <div class="flex-shrink-0">
                <div class="sa-title">@yield('page-title', 'Mi panel')</div>
                <div class="sa-subtitle">@yield('page-subtitle', 'Panel de superadministración · MSkinWellness')</div>
            </div>


            <div class="d-flex align-items-center gap-2">

                <div class="dropdown">
                    <button type="button" class="sa-avatar" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Menú de cuenta">
                        {{ mb_strtoupper(mb_substr(auth()->user()->name ?? '?', 0, 1)) }}
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a href="{{ route('admin.account.edit') }}" class="dropdown-item">
                                <i class="bi bi-person me-2"></i> Mi cuenta
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="{{ route('admin.logout') }}" class="m-0">
                                @csrf
                                <button type="submit" class="dropdown-item">
                                    <i class="bi bi-box-arrow-right me-2"></i> Cerrar sesión
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <div class="sa-content p-3 p-md-4">
            @yield('content')
        </div>

    </main>
</div>

@stack('scripts')

</body>
</html>
