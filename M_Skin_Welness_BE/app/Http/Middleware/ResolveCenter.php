<?php

namespace App\Http\Middleware;

use App\Models\Center;
use Closure;
use Illuminate\Http\Request;

class ResolveCenter
{
    public const HEADER = 'X-Center-Slug';

    public function handle(Request $request, Closure $next)
    {
        $center = null;

        $user = $request->user();
        if ($user !== null && $user->center_id !== null) {
            $center = Center::query()
                ->whereKey($user->center_id)
                ->where('is_active', true)
                ->with('plan')
                ->first();
        } else {
            $slug = $request->header(self::HEADER);
            if (is_string($slug) && $slug !== '') {
                $center = Center::query()
                    ->where('slug', $slug)
                    ->where('is_active', true)
                    ->with('plan')
                    ->first();
            }
        }

        if ($center) {
            $request->attributes->set('currentCenter', $center);
        }

        return $next($request);
    }
}

