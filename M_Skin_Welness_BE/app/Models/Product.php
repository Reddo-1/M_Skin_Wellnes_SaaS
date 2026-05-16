<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    protected $fillable = [
        'center_id',
        'name',
        'description',
        'measurement_unit',
        'sale_price',
        'cost_price',
        'minimum_stock',
        'is_sellable',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sale_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'minimum_stock' => 'decimal:3',
            'is_sellable' => 'boolean',
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

    public function stock(): HasOne
    {
        return $this->hasOne(ProductStock::class);
    }
}
