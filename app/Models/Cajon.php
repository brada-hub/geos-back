<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cajon extends Model
{
    protected $table = 'cajones';

    protected $fillable = ['mueble_id', 'fila', 'columna', 'etiqueta', 'apellido_desde', 'apellido_hasta'];

    public function mueble(): BelongsTo
    {
        return $this->belongsTo(Mueble::class);
    }

    public function empleados(): HasMany
    {
        return $this->hasMany(Empleado::class)
            ->orderBy('primer_apellido')
            ->orderBy('segundo_apellido')
            ->orderBy('nombres');
    }

    public function tiposContratoPermitidos(): BelongsToMany
    {
        return $this->belongsToMany(TipoContrato::class, 'cajon_tipo_contrato', 'cajon_id', 'tipo_contrato_id')
                    ->withTimestamps();
    }

    /**
     * Verifica si este cajón admite un tipo de contrato específico.
     * Si no tiene restricciones asignadas (está vacío), admite cualquiera (mezcla libre).
     */
    public function admiteTipoContrato($tipoContratoId): bool
    {
        if (empty($tipoContratoId)) {
            return true;
        }

        $permitidos = $this->tiposContratoPermitidos()->pluck('tipos_contrato.id');
        if ($permitidos->isEmpty()) {
            return true; // Libre / Mixto
        }

        return $permitidos->contains((int) $tipoContratoId);
    }

    public function sedesPermitidas(): BelongsToMany
    {
        return $this->belongsToMany(Sede::class, 'cajon_sede', 'cajon_id', 'sede_id');
    }

    /**
     * Verifica si este cajón admite una sede específica.
     * Si no tiene restricciones asignadas (está vacío), admite cualquiera (mezcla libre).
     */
    public function admiteSede($sedeId): bool
    {
        if (empty($sedeId)) {
            return true;
        }

        $permitidas = $this->sedesPermitidas()->pluck('sedes.id');
        if ($permitidas->isEmpty()) {
            return true; // Libre / Mixto
        }

        return $permitidas->contains((int) $sedeId);
    }

    /**
     * Verifica si este cajón admite un apellido por rango alfabético.
     * Permite prefijos, letras (ej: A - M incluye MAMANI) y apellidos completos.
     */
    public function admiteApellido($primerApellido): bool
    {
        if (empty($this->apellido_desde) && empty($this->apellido_hasta)) {
            return true;
        }

        if (empty($primerApellido)) {
            return true;
        }

        $ap = mb_strtoupper(trim($primerApellido));

        if (!empty($this->apellido_desde)) {
            $desde = mb_strtoupper(trim($this->apellido_desde));
            if ($ap < $desde) {
                return false;
            }
        }

        if (!empty($this->apellido_hasta)) {
            $hasta = mb_strtoupper(trim($this->apellido_hasta));
            // Si el límite es 'M', incluir todas las palabras que comienzan con 'M' (ej: 'MAMANI' < 'MZZZZ')
            $limiteSuperior = $hasta . 'ZZZZ';
            if ($ap > $limiteSuperior) {
                return false;
            }
        }

        return true;
    }

    /**
     * Verifica si un empleado cumple todas las reglas de este cajón (Régimen, Sede y Apellido).
     */
    public function admiteEmpleado(Empleado $emp): bool
    {
        return $this->admiteTipoContrato($emp->tipo_contrato_id)
            && $this->admiteSede($emp->sede_id)
            && $this->admiteApellido($emp->primer_apellido);
    }
}
