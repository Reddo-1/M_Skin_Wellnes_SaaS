<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{Builder, Model};
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, HasMany};

class Machine extends Model
{
    use HasFactory;

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

    public function scopeForCenter(Builder $query, int $centerId): Builder
    {
        return $query->where('center_id', $centerId);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function fixedRoom(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'fixed_room_id');
    }

    public function treatments(): BelongsToMany
    {
        return $this->belongsToMany(Treatment::class, 'machine_treatment')
            ->withPivot(['center_id']);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
