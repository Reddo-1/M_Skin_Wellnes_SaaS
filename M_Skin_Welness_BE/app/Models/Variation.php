<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Variation extends Model
{
    public $timestamps = false;

    protected $fillable = ['name'];
}
