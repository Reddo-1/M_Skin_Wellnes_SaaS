<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'center_id',
        'sale_id',
        'client_id',
        'invoice_number',
        'issued_date',
        'subtotal',
        'vat_percentage',
        'vat_amount',
        'total',
        'client_snapshot',
        'center_snapshot',
        'pdf_path',
        'issued_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'issued_date' => 'date',
            'subtotal' => 'decimal:2',
            'vat_percentage' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'client_snapshot' => 'array',
            'center_snapshot' => 'array',
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

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_user_id');
    }
}
