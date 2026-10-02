<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MuebleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'filas' => $this->filas,
            'columnas' => $this->columnas,
            'sede_id' => $this->sede_id,
            'sede' => $this->sede ? [
                'id' => $this->sede->id,
                'nombre' => $this->sede->nombre,
            ] : null,
            'cajones' => $this->cajones->map(fn($cajon) => [
                'id' => $cajon->id,
                'fila' => $cajon->fila,
                'columna' => $cajon->columna,
                'etiqueta' => $cajon->etiqueta,
                'apellido_desde' => $cajon->apellido_desde,
                'apellido_hasta' => $cajon->apellido_hasta,
                'tipos_contrato_permitidos' => $cajon->tiposContratoPermitidos ? $cajon->tiposContratoPermitidos->map(fn($tc) => [
                    'id' => $tc->id,
                    'nombre' => $tc->nombre,
                    'codigo' => $tc->codigo,
                    'color' => $tc->color,
                ]) : [],
                'sedes_permitidas' => $cajon->sedesPermitidas ? $cajon->sedesPermitidas->map(fn($s) => [
                    'id' => $s->id,
                    'nombre' => $s->nombre,
                ]) : [],
                'empleados' => $cajon->empleados->map(fn($empleado) => [
                    'id' => $empleado->id,
                    'numero_unico' => $empleado->numero_unico,
                    'codigo_archivo' => $empleado->codigo_archivo,
                    'nombre_completo' => $empleado->nombre_completo,
                    'nombres' => $empleado->nombres,
                    'primer_apellido' => $empleado->primer_apellido,
                    'segundo_apellido' => $empleado->segundo_apellido,
                    'documento_identidad' => $empleado->documento_identidad,
                    'fecha_nacimiento' => $empleado->fecha_nacimiento,
                    'sexo_id' => $empleado->sexo_id,
                    'sexo' => $empleado->sexoRelacion ? [
                        'id' => $empleado->sexoRelacion->id,
                        'nombre' => $empleado->sexoRelacion->nombre,
                        'codigo' => $empleado->sexoRelacion->codigo,
                    ] : ($empleado->sexo ?? 'M'),
                    'cargo' => $empleado->cargo,
                    'cargo_id' => $empleado->cargo_id,
                    'tipo_contrato_id' => $empleado->tipo_contrato_id,
                    'tipo_contrato' => $empleado->tipoContrato ? [
                        'id' => $empleado->tipoContrato->id,
                        'nombre' => $empleado->tipoContrato->nombre,
                        'codigo' => $empleado->tipoContrato->codigo,
                        'color' => $empleado->tipoContrato->color,
                    ] : null,
                    'sede_id' => $empleado->sede_id,
                    'sede' => $empleado->sede ? [
                        'id' => $empleado->sede->id,
                        'nombre' => $empleado->sede->nombre,
                    ] : null,
                    'cajon_id' => $empleado->cajon_id,
                    'estado' => $empleado->estado,
                ]),
            ]),
        ];
    }
}
