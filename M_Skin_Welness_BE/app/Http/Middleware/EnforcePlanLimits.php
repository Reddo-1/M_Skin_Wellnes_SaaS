<?php

namespace App\Http\Middleware;

use App\Support\CurrentCenter;
use Closure;
use Illuminate\Http\Request;

class EnforcePlanLimits
{
    public function handle(Request $request, Closure $next, string $capability)
    {
        $center = CurrentCenter::get($request);

        if (!$center) {
            return response()->json(['message' => 'Centro no resuelto.'], 400);
        }

        $plan = $center->plan;
        if (!$plan) {
            return response()->json(['message' => 'El centro no tiene plan asignado.'], 400);
        }

        $allowed = match ($capability) {
            'allows_online_clients' => (bool) $plan->allows_online_clients,
            'allows_emails' => (bool) $plan->allows_emails,
            'allows_public_page' => (bool) $plan->allows_public_page,
            'allows_custom_domain' => (bool) $plan->allows_custom_domain,
            default => null,
        };

        if ($allowed === null) {
            return response()->json(['message' => 'Capacidad no soportada.'], 400);
        }

        if ($allowed !== true) {
            return response()->json(['message' => 'Tu plan no permite esta acción.'], 403);
        }

        return $next($request);
    }
}

