<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Movimiento extends Model
{
    protected $fillable = [
        'empleado_id',
        'cajon_origen_id',
        'cajon_destino_id',
        'comentario',
        'estado_anterior',
        'estado_nuevo',
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function cajonOrigen(): BelongsTo
    {
        return $this->belongsTo(Cajon::class, 'cajon_origen_id');
    }

    public function cajonDestino(): BelongsTo
    {
        return $this->belongsTo(Cajon::class, 'cajon_destino_id');
    }
}
