<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{Builder, Model};
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class ClientProfile extends Model
{
    protected $fillable = [
        'center_id',
        'user_id',
        'body_type',
        'current_skin_evaluation_id',
        'general_notes',
    ];

    public function scopeForCenter(Builder $query, int $centerId): Builder
    {
        return $query->where('center_id', $centerId);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function currentEvaluation(): BelongsTo
    {
        return $this->belongsTo(SkinEvaluation::class, 'current_skin_evaluation_id');
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(SkinEvaluation::class);
    }
}
