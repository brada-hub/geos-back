<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Movimiento extends Model
{
    protected $fillable = [
        'empleado_id',
        'tipo_movimiento',
        'cajon_origen_id',
        'cajon_destino_id',
        'tipo_contrato_anterior_id',
        'tipo_contrato_nuevo_id',
        'cargo_anterior',
        'cargo_nuevo',
        'sede_origen_id',
        'sede_destino_id',
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
        return $this->belongsTo(Cajon::class, 'cajon_origen_id')->with('mueble.sede');
    }

    public function cajonDestino(): BelongsTo
    {
        return $this->belongsTo(Cajon::class, 'cajon_destino_id')->with('mueble.sede');
    }

    public function tipoContratoAnterior(): BelongsTo
    {
        return $this->belongsTo(TipoContrato::class, 'tipo_contrato_anterior_id');
    }

    public function tipoContratoNuevo(): BelongsTo
    {
        return $this->belongsTo(TipoContrato::class, 'tipo_contrato_nuevo_id');
    }

    public function sedeOrigen(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'sede_origen_id');
    }

    public function sedeDestino(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'sede_destino_id');
    }
}
