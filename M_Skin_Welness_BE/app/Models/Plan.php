<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'max_workers',
        'allows_online_clients',
        'allows_emails',
        'allows_public_page',
        'allows_custom_domain',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'max_workers' => 'integer',
            'allows_online_clients' => 'boolean',
            'allows_emails' => 'boolean',
            'allows_public_page' => 'boolean',
            'allows_custom_domain' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function centers(): HasMany
    {
        return $this->hasMany(Center::class);
    }
}

