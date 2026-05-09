<?php

namespace App\Http\Middleware;

use App\Models\Center;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ResolveCenterScope
{

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            throw new HttpException(401, 'Unauthenticated.');
        }

        if ($user->center_id !== null) {
            $request->attributes->set('center_id', (int) $user->center_id);

            return $next($request);
        }

        if (! $user->hasRole('superadmin')) {
            throw new HttpException(403, 'User has no center assigned.');
        }

        $rawCenterId = $request->input('center_id') ?? $request->query('center_id');

        if ($rawCenterId === null || !ctype_digit((string) $rawCenterId)) {
            throw new HttpException(422, 'Superadmin must provide center_id for impersonation.');
        }

        $centerId = (int) $rawCenterId;

        if (!Center::query()->whereKey($centerId)->exists()) {
            throw new HttpException(422, "Center {$centerId} does not exist.");
        }

        $request->attributes->set('center_id', $centerId);

        return $next($request);
    }
}
