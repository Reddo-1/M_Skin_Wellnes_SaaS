<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory;

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

    public function scopeForCenter(Builder $query, int $centerId): Builder
    {
        return $query->where('center_id', $centerId);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function machines(): HasMany
    {
        return $this->hasMany(Machine::class, 'fixed_room_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
