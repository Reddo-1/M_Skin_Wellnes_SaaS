<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Center extends Model
{
    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'custom_domain',
        'is_domain_verified',
        'plan_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'uuid' => 'string',
            'is_domain_verified' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}

