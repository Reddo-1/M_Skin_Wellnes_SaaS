<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class Center extends Model
{
    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'custom_domain',
        'is_domain_verified',
        'plan_id',
        'billing_user_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_domain_verified' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function billingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'billing_user_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
