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
            'cajones' => $this->cajones->map(fn($cajon) => [
                'id' => $cajon->id,
                'fila' => $cajon->fila,
                'columna' => $cajon->columna,
                'etiqueta' => $cajon->etiqueta,
                'empleados' => $cajon->empleados->map(fn($empleado) => [
                    'id' => $empleado->id,
                    'numero_unico' => $empleado->numero_unico,
                    'codigo_archivo' => $empleado->codigo_archivo,
                    'nombre_completo' => $empleado->nombre_completo,
                    'cargo' => $empleado->cargo,
                    'cajon_id' => $empleado->cajon_id,
                    'estado' => $empleado->estado,
                ]),
            ]),
        ];
    }
}
