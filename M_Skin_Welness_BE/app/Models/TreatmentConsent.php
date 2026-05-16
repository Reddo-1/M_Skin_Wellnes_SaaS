<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreatmentConsent extends Model
{
    protected $fillable = [
        'center_id',
        'user_id',
        'treatment_id',
        'reviewed_by_user_id',
        'review_date',
        'is_suitable',
        'unsuitability_reason',
        'treatment_consent',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'review_date' => 'date',
            'is_suitable' => 'boolean',
            'treatment_consent' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function scopeForCenter(Builder $query, int $centerId): Builder
    {
        return $query->where('center_id', $centerId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function treatment(): BelongsTo
    {
        return $this->belongsTo(Treatment::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}
