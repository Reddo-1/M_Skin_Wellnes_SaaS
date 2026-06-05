<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AbsenceType extends Model
{
    public $timestamps = false;

    protected $fillable = ['name'];
}
