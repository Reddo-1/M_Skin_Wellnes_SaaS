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

    //devuelve el id del estado de la sesión por el nombre
    public static function idFor(string $name): int
    {
        return static::query()->where('name', $name)->value('id')
            ?? throw new \RuntimeException("El estado de sesión '{$name}' no está disponible.");
    }
}
