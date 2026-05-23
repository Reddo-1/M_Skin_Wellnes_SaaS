<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SessionStatus extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'sort_order'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }
}
