<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{Builder, Model};
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductStock extends Model
{
    protected $fillable = [
        'center_id',
        'product_id',
        'current_quantity',
    ];

    protected function casts(): array
    {
        return [
            'current_quantity' => 'decimal:3',
        ];
    }

    public function scopeForCenter(Builder $query, int $centerId): Builder
    {
        return $query->where('center_id', $centerId);
    }

    public function scopeBelowMinimum(Builder $query): Builder
    {
        return $query
            ->join('products', 'product_stocks.product_id', '=', 'products.id')
            ->whereColumn('product_stocks.current_quantity', '<', 'products.minimum_stock')
            ->select('product_stocks.*');
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
