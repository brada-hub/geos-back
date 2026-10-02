<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empleado extends Model
{
    protected $table = 'empleados';

    protected $fillable = [
        'codigo_archivo',
        'cajon_id',
        'nombre_completo',
        'nombres',
        'primer_apellido',
        'segundo_apellido',
        'documento_identidad',
        'fecha_nacimiento',
        'sexo',
        'sexo_id',
        'cargo_id',
        'cargo',
        'tipo_contrato_id',
        'sede_id',
        'numero_unico',
        'estado'
    ];

    protected $appends = ['nombre_completo_calculado'];

    public static function booted()
    {
        static::saving(function ($empleado) {
            if (!empty($empleado->nombres) && !empty($empleado->primer_apellido)) {
                $parts = [$empleado->nombres, $empleado->primer_apellido];
                if (!empty($empleado->segundo_apellido)) {
                    $parts[] = $empleado->segundo_apellido;
                }
                $empleado->nombre_completo = implode(' ', $parts);
            }
        });
    }

    public function getNombreCompletoCalculadoAttribute(): string
    {
        if (!empty($this->nombres) && !empty($this->primer_apellido)) {
            $parts = [$this->nombres, $this->primer_apellido];
            if (!empty($this->segundo_apellido)) {
                $parts[] = $this->segundo_apellido;
            }
            return implode(' ', $parts);
        }
        return $this->nombre_completo ?? '';
    }

    public function cargoRelacion(): BelongsTo
    {
        return $this->belongsTo(Cargo::class, 'cargo_id');
    }

    public function tipoContrato(): BelongsTo
    {
        return $this->belongsTo(TipoContrato::class, 'tipo_contrato_id');
    }

    public function sexoRelacion(): BelongsTo
    {
        return $this->belongsTo(Sexo::class, 'sexo_id');
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'sede_id');
    }

    public function cajon(): BelongsTo
    {
        return $this->belongsTo(Cajon::class);
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(Movimiento::class)->orderBy('created_at', 'desc');
    }
}
