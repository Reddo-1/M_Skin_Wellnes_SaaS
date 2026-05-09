<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Room extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'center_id',
        'name',
        'grid_position',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'grid_position' => 'array',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }
}
