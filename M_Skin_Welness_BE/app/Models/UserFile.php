<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserFile extends Model
{
    public const UPDATED_AT = null;

    //categorias clinicas: van vinculadas a una skin_evaluation
    public const CATEGORIES_CLINICAL = [
        'facial_frontal',
        'facial_izquierdo',
        'facial_derecho',
        'corporal_frontal',
        'corporal_trasero',
        'corporal_izquierdo',
        'corporal_derecho',
    ];

    //avatar del usuario, no requiere skin_evaluation
    public const CATEGORY_AVATAR = 'foto_perfil';

    //firma manuscrita del paciente para un consentimiento de tratamiento
    public const CATEGORY_CONSENT_SIGNATURE = 'firma_consentimiento';

    protected $fillable = [
        'center_id',
        'user_id',
        'skin_evaluation_id',
        'category',
        'path',
        'notes',
    ];

    public function scopeForCenter(Builder $query, int $centerId): Builder
    {
        return $query->where('center_id', $centerId);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function skinEvaluation(): BelongsTo
    {
        return $this->belongsTo(SkinEvaluation::class);
    }

    public function isAvatar(): bool
    {
        return $this->category === self::CATEGORY_AVATAR;
    }
}
