<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{Builder, Model};
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CenterFile extends Model
{
    public const UPDATED_AT = null;

    public const TYPES = [
        'logo',
        'header',
        'default_avatar',
    ];

    protected $fillable = [
        'center_id',
        'type',
        'path',
        'mime_type',
    ];

    public function scopeForCenter(Builder $query, int $centerId): Builder
    {
        return $query->where('center_id', $centerId);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }
}
