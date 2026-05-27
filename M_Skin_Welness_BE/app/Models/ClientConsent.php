<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{Builder, Model};
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientConsent extends Model
{
    protected $fillable = [
        'center_id',
        'user_id',
        'reviewed_by_user_id',
        'clinical_photos_consent',
        'marketing_data_consent',
        'commercial_images_consent',
        'signature_user_file_id',
        'pdf_user_file_id',
        'signed_at',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'signed_at' => 'datetime',
            'clinical_photos_consent' => 'boolean',
            'marketing_data_consent' => 'boolean',
            'commercial_images_consent' => 'boolean',
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

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function signatureFile(): BelongsTo
    {
        return $this->belongsTo(UserFile::class, 'signature_user_file_id');
    }

    public function pdfFile(): BelongsTo
    {
        return $this->belongsTo(UserFile::class, 'pdf_user_file_id');
    }
}
