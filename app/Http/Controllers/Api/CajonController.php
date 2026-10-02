<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cajon;
use App\Models\Mueble;
use App\Models\Empleado;
use App\Models\Movimiento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CajonController extends Controller
{
    /**
     * Add a new drawer to a furniture.
     */
    public function store(Request $request, Mueble $mueble): JsonResponse
    {
        $maxFila = $mueble->cajones()->max('fila') ?? 0;
        $cajon = Cajon::create([
            'mueble_id' => $mueble->id,
            'fila' => $maxFila + 1,
            'columna' => 1,
            'etiqueta' => $request->input('etiqueta'),
        ]);

        $mueble->update([
            'filas' => max($mueble->filas, $maxFila + 1)
        ]);

        return response()->json([
            'message' => 'Gaveta añadida correctamente',
            'cajon' => $cajon
        ], 201);
    }

    /**
     * Update the drawer label.
     */
    public function update(Request $request, Cajon $cajon): JsonResponse
    {
        $validated = $request->validate([
            'etiqueta' => 'nullable|string|max:255',
        ]);

        $cajon->update($validated);

        return response()->json([
            'message' => 'Cajón actualizado correctamente',
            'cajon' => $cajon
        ]);
    }

    /**
     * Delete an individual drawer.
     */
    public function destroy(Cajon $cajon): JsonResponse
    {
        $mueble = $cajon->mueble;
        $cajon->delete();

        if ($mueble) {
            $total = $mueble->cajones()->count();
            $mueble->update(['filas' => $total]);
        }

        return response()->json([
            'message' => 'Gaveta eliminada correctamente'
        ]);
    }

    /**
     * Move an individual drawer to another furniture.
     */
    public function mover(Request $request, Cajon $cajon): JsonResponse
    {
        $validated = $request->validate([
            'mueble_destino_id' => 'required|exists:muebles,id',
        ]);

        $muebleOrigen = $cajon->mueble;
        $muebleDestino = Mueble::findOrFail($validated['mueble_destino_id']);

        $maxFilaDestino = Cajon::where('mueble_id', $muebleDestino->id)->max('fila') ?? 0;

        $cajon->update([
            'mueble_id' => $muebleDestino->id,
            'fila' => $maxFilaDestino + 1,
            'columna' => 1,
        ]);

        $muebleDestino->update([
            'filas' => max($muebleDestino->filas, $maxFilaDestino + 1),
        ]);

        if ($muebleOrigen) {
            $totalOrigen = Cajon::where('mueble_id', $muebleOrigen->id)->count();
            $muebleOrigen->update(['filas' => max(1, $totalOrigen)]);
        }

        return response()->json([
            'message' => "Gaveta trasladada a \"{$muebleDestino->nombre}\"",
        ]);
    }

    /**
     * Update allowed contract types and sedes for this drawer.
     */
    public function updateReglas(Request $request, Cajon $cajon): JsonResponse
    {
        $validated = $request->validate([
            'tipos_contrato_ids' => 'nullable|array',
            'tipos_contrato_ids.*' => 'exists:tipos_contrato,id',
            'sedes_ids' => 'nullable|array',
            'sedes_ids.*' => 'exists:sedes,id',
            'apellido_desde' => 'nullable|string|max:50',
            'apellido_hasta' => 'nullable|string|max:50',
            'auto_sincronizar' => 'nullable|boolean',
        ]);

        if (array_key_exists('tipos_contrato_ids', $validated)) {
            $cajon->tiposContratoPermitidos()->sync($validated['tipos_contrato_ids'] ?? []);
        }

        if (array_key_exists('sedes_ids', $validated)) {
            $cajon->sedesPermitidas()->sync($validated['sedes_ids'] ?? []);
        }

        $cajon->update([
            'apellido_desde' => !empty($validated['apellido_desde']) ? trim($validated['apellido_desde']) : null,
            'apellido_hasta' => !empty($validated['apellido_hasta']) ? trim($validated['apellido_hasta']) : null,
        ]);

        $syncResult = null;
        if (!empty($validated['auto_sincronizar'])) {
            $syncResult = $this->ejecutarSincronizacion($cajon);
        }

        return response()->json([
            'message' => 'Reglas de admisión de la gaveta actualizadas',
            'cajon' => $cajon->load(['tiposContratoPermitidos', 'sedesPermitidas']),
            'sync' => $syncResult,
        ]);
    }

    /**
     * Sincronizar automáticamente la gaveta con base en sus reglas:
     * 1. Devuelve a la Gaveta Virtual los expedientes que ya no cumplan.
     * 2. Absorbe de la Gaveta Virtual todos los expedientes que sí cumplan.
     */
    public function sincronizarAutomatico(Cajon $cajon): JsonResponse
    {
        $syncResult = $this->ejecutarSincronizacion($cajon);

        return response()->json([
            'message' => "Gaveta sincronizada: {$syncResult['asignados']} expedientes añadidos, {$syncResult['expulsados']} devueltos a Gaveta Virtual.",
            'asignados' => $syncResult['asignados'],
            'expulsados' => $syncResult['expulsados'],
            'total_gaveta' => $cajon->empleados()->count(),
        ]);
    }

    private function ejecutarSincronizacion(Cajon $cajon): array
    {
        $expulsados = 0;
        $asignados = 0;

        // 1. Expulsar de la gaveta los que ya NO cumplan las reglas
        $empleadosActuales = $cajon->empleados;
        foreach ($empleadosActuales as $emp) {
            if (!$cajon->admiteEmpleado($emp)) {
                $emp->update(['cajon_id' => null]);
                Movimiento::create([
                    'empleado_id' => $emp->id,
                    'tipo_movimiento' => 'ubicacion',
                    'cajon_origen_id' => $cajon->id,
                    'cajon_destino_id' => null,
                    'comentario' => 'Movido a Gaveta Virtual por ajuste en las reglas de admisión de la gaveta',
                ]);
                $expulsados++;
            }
        }

        // 2. Traer de la Gaveta Virtual (cajon_id = null) todos los que SÍ cumplan las reglas
        $candidatosVirtuales = Empleado::whereNull('cajon_id')->get();
        foreach ($candidatosVirtuales as $candidato) {
            if ($cajon->admiteEmpleado($candidato)) {
                $candidato->update(['cajon_id' => $cajon->id]);
                Movimiento::create([
                    'empleado_id' => $candidato->id,
                    'tipo_movimiento' => 'ubicacion',
                    'cajon_origen_id' => null,
                    'cajon_destino_id' => $cajon->id,
                    'comentario' => 'Asignado automáticamente por sincronización de reglas de gaveta',
                ]);
                $asignados++;
            }
        }

        return [
            'asignados' => $asignados,
            'expulsados' => $expulsados,
        ];
    }

    /**
     * Asignar masivamente expedientes sin gaveta pertenecientes a una sede hacia este cajón con 1 clic.
     */
    public function asignarPorSede(Request $request, Cajon $cajon): JsonResponse
    {
        $validated = $request->validate([
            'sede_id' => 'required|exists:sedes,id',
        ]);

        $sedeId = (int) $validated['sede_id'];

        if (!$cajon->admiteSede($sedeId)) {
            $sedesPermitidas = $cajon->sedesPermitidas()->pluck('nombre')->join(', ');
            return response()->json([
                'message' => "Esta gaveta no admite expedientes de la sede seleccionada. Solo admite: {$sedesPermitidas}."
            ], 422);
        }

        // Obtener personal sin cajón asignado de esa sede
        $candidatos = Empleado::whereNull('cajon_id')
            ->where('sede_id', $sedeId)
            ->get();

        if ($candidatos->isEmpty()) {
            return response()->json([
                'message' => 'No hay expedientes sin asignar para la sede seleccionada.',
                'asignados' => 0,
                'omitidos' => 0,
            ]);
        }

        $asignados = 0;
        $omitidos = 0;

        foreach ($candidatos as $empleado) {
            if ($cajon->admiteTipoContrato($empleado->tipo_contrato_id) && $cajon->admiteApellido($empleado->primer_apellido)) {
                $empleado->update(['cajon_id' => $cajon->id]);
                Movimiento::create([
                    'empleado_id' => $empleado->id,
                    'tipo_movimiento' => 'ubicacion',
                    'cajon_origen_id' => null,
                    'cajon_destino_id' => $cajon->id,
                    'comentario' => "Asignación masiva desde gaveta virtual por sede ({$empleado->sede?->nombre})",
                ]);
                $asignados++;
            } else {
                $omitidos++;
            }
        }

        $mensaje = "Se asignaron {$asignados} expediente(s) a la gaveta.";
        if ($omitidos > 0) {
            $mensaje .= " {$omitidos} expediente(s) no fueron asignados porque su tipo de contrato no coincide con las reglas de la gaveta.";
        }

        return response()->json([
            'message' => $mensaje,
            'asignados' => $asignados,
            'omitidos' => $omitidos,
        ]);
    }
}
