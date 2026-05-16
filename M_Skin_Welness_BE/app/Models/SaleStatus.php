<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleStatus extends Model
{
    public $timestamps = false;

    protected $fillable = ['name'];

    public static function idFor(string $name): int
    {
        return (int) self::query()->where('name', $name)->value('id');
    }
}
