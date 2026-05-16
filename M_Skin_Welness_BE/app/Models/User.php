<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    protected $fillable = [
        'center_id',
        'name',
        'email',
        'phone',
        'birth_date',
        'password',
        'registration_source',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birth_date' => 'date',
            'is_active' => 'boolean',
            'failed_attempts' => 'integer',
            'locked_until' => 'datetime',
        ];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function scopeForCenter(Builder $query, int $centerId): Builder
    {
        return $query->where('center_id', $centerId);
    }

    public function scopeActive(Builder $query, bool $active = true): Builder
    {
        return $query->where('is_active', $active);
    }

    //usamos la notification propia para que el correo salga en espanol con tono de MSkinWellness
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
