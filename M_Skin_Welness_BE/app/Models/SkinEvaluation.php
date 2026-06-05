<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{Builder, Model};
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, HasMany};

class SkinEvaluation extends Model
{
    protected $fillable = [
        'center_id',
        'user_id',
        'client_profile_id',
        'skin_type_id',
        'evaluation_date',
        'professional_id',
        'general_notes',
    ];

    protected function casts(): array
    {
        return [
            'evaluation_date' => 'date',
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

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function clientProfile(): BelongsTo
    {
        return $this->belongsTo(ClientProfile::class);
    }

    public function skinType(): BelongsTo
    {
        return $this->belongsTo(SkinType::class);
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professional_id');
    }

    public function variations(): BelongsToMany
    {
        //el pivote solo tiene created_at (no updated_at), asi que withTimestamps() rompia el select; created_at ya usa default useCurrent
        return $this->belongsToMany(Variation::class, 'skin_evaluation_variation');
    }

    //las 7 imagenes clinicas se distinguen del resto de archivos por su category
    public function files(): HasMany
    {
        return $this->hasMany(UserFile::class);
    }
}
