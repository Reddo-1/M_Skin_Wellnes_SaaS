<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Models\Role;

class Treatment extends Model
{
    use HasFactory;

    protected $fillable = [
        'center_id',
        'name',
        'duration_minutes',
        'price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'price' => 'decimal:2',
            'is_active' => 'boolean',
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

    public function machines(): BelongsToMany
    {
        return $this->belongsToMany(Machine::class, 'machine_treatment')
            ->withPivot(['center_id']);
    }

    public function authorizedRoles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_treatment')
            ->withPivot(['center_id']);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
