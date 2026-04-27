<?php

namespace App\Support;

use App\Models\Center;
use Illuminate\Http\Request;

class CurrentCenter
{
    public static function get(Request $request): ?Center
    {
        $center = $request->attributes->get('currentCenter');

        return $center instanceof Center ? $center : null;
    }
}

