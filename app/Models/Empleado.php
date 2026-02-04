<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Empleado extends Model
{
    protected $table = 'empleados';

    protected $fillable = ['codigo_archivo', 'cajon_id', 'nombre_completo', 'cargo', 'numero_unico', 'estado'];

    public function cajon(): BelongsTo
    {
        return $this->belongsTo(Cajon::class);
    }

    public function movimientos()
    {
        return $this->hasMany(Movimiento::class)->orderBy('created_at', 'desc');
    }
}
