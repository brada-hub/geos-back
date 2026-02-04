<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cajon extends Model
{
    protected $table = 'cajones';

    protected $fillable = ['mueble_id', 'fila', 'columna', 'etiqueta'];

    public function mueble(): BelongsTo
    {
        return $this->belongsTo(Mueble::class);
    }

    public function empleados(): HasMany
    {
        return $this->hasMany(Empleado::class);
    }
}
