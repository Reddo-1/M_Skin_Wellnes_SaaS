<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovementType extends Model
{
    public $timestamps = false;

    protected $fillable = ['name'];
}
