<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Machine extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'center_id',
        'name',
        'is_active',
        'is_mobile',
        'fixed_room_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_mobile' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function fixedRoom(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'fixed_room_id');
    }
}
