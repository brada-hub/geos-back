<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMuebleRequest;
use App\Http\Resources\MuebleResource;
use App\Models\Cajon;
use App\Models\Mueble;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MuebleController extends Controller
{
    /**
     * Get all furniture with nested drawers and employees.
     */
    public function index(): AnonymousResourceCollection
    {
        $muebles = Mueble::with([
            'cajones.empleados.tipoContrato',
            'cajones.empleados.sexoRelacion',
            'cajones.empleados.sede',
            'cajones.tiposContratoPermitidos',
            'cajones.sedesPermitidas',
            'sede'
        ])->get();
        return MuebleResource::collection($muebles);
    }

    /**
     * Store a new furniture and generate its drawers.
     */
    public function store(StoreMuebleRequest $request): JsonResponse
    {
        $data = $request->validated();
        if (empty($data['sede_id'])) {
            $sede = \App\Models\Sede::firstOrCreate(['nombre' => 'Sede Principal']);
            $data['sede_id'] = $sede->id;
        }

        $mueble = Mueble::create($data);

        // Generar cajones automáticamente basados en matriz
        for ($f = 1; $f <= $mueble->filas; $f++) {
            for ($c = 1; $c <= $mueble->columnas; $c++) {
                Cajon::create([
                    'mueble_id' => $mueble->id,
                    'fila' => $f,
                    'columna' => $c,
                ]);
            }
        }

        return response()->json([
            'message' => 'Mueble creado con éxito y gavetas generadas',
            'mueble' => new MuebleResource($mueble)
        ], 201);
    }

    /**
     * Update furniture details.
     */
    public function update(Request $request, Mueble $mueble): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
        ]);

        $mueble->update($validated);

        return response()->json([
            'message' => 'Mueble actualizado correctamente',
            'mueble' => new MuebleResource($mueble)
        ]);
    }

    /**
     * Delete furniture and cascade its drawers.
     */
    public function destroy(Mueble $mueble): JsonResponse
    {
        $mueble->delete();

        return response()->json([
            'message' => 'Mueble eliminado correctamente'
        ]);
    }

    /**
     * Extract a whole column into a new independent single-column furniture.
     */
    public function extraerColumna(Request $request, Mueble $mueble): JsonResponse
    {
        $validated = $request->validate([
            'columna' => 'required|integer|min:1',
            'nombre' => 'nullable|string|max:255',
        ]);

        $col = (int) $validated['columna'];
        $cajonesColumna = Cajon::where('mueble_id', $mueble->id)->where('columna', $col)->get();

        if ($cajonesColumna->isEmpty()) {
            return response()->json(['message' => 'No hay gavetas en esa columna'], 422);
        }

        $letter = chr(64 + $col);
        $nuevoNombre = !empty($validated['nombre'])
            ? $validated['nombre']
            : $mueble->nombre . " (Torre {$letter})";

        // Crear el nuevo archivador independiente
        $nuevoMueble = Mueble::create([
            'sede_id' => $mueble->sede_id,
            'nombre' => $nuevoNombre,
            'filas' => $cajonesColumna->count(),
            'columnas' => 1,
        ]);

        // Mover los cajones al nuevo mueble
        foreach ($cajonesColumna as $cajon) {
            $cajon->update([
                'mueble_id' => $nuevoMueble->id,
                'columna' => 1,
            ]);
        }

        // Reindexar columnas restantes
        $restantes = Cajon::where('mueble_id', $mueble->id)->distinct()->orderBy('columna')->pluck('columna');
        if ($restantes->isEmpty()) {
            $mueble->delete();
        } else {
            $newCol = 1;
            foreach ($restantes as $oldCol) {
                if ($oldCol != $newCol) {
                    Cajon::where('mueble_id', $mueble->id)->where('columna', $oldCol)->update(['columna' => $newCol]);
                }
                $newCol++;
            }
            $mueble->update(['columnas' => count($restantes)]);
        }

        return response()->json([
            'message' => "Columna {$letter} extraída como \"{$nuevoNombre}\"",
            'nuevo_mueble' => $nuevoMueble,
        ]);
    }

    /**
     * Split a multi-column furniture into individual 1-column towers.
     */
    public function desacoplar(Mueble $mueble): JsonResponse
    {
        $cajones = Cajon::where('mueble_id', $mueble->id)->get();
        $cols = $cajones->pluck('columna')->unique()->sort();

        foreach ($cols as $col) {
            $letter = chr(64 + $col);
            $cajonesColumna = $cajones->where('columna', $col);

            $nuevoMueble = Mueble::create([
                'sede_id' => $mueble->sede_id,
                'nombre' => $mueble->nombre . " - Torre {$letter}",
                'filas' => $cajonesColumna->count(),
                'columnas' => 1,
            ]);

            foreach ($cajonesColumna as $cajon) {
                $cajon->update([
                    'mueble_id' => $nuevoMueble->id,
                    'columna' => 1,
                ]);
            }
        }

        $mueble->delete();

        return response()->json([
            'message' => 'Mueble dividido en torres individuales exitosamente'
        ]);
    }

    /**
     * Merge this furniture into another target furniture as additional columns.
     */
    public function fusionar(Request $request, Mueble $mueble): JsonResponse
    {
        $validated = $request->validate([
            'mueble_destino_id' => 'required|exists:muebles,id',
        ]);

        $destinoId = (int) $validated['mueble_destino_id'];
        if ($mueble->id === $destinoId) {
            return response()->json(['message' => 'No puedes fusionar un mueble consigo mismo'], 422);
        }

        $muebleDestino = Mueble::findOrFail($destinoId);

        // Columna base en destino
        $baseCol = $muebleDestino->cajones()->max('columna') ?? 0;

        $cajonesOrigen = $mueble->cajones()->get();
        $colsOrigen = $cajonesOrigen->pluck('columna')->unique()->sort()->values();

        $colMapping = [];
        foreach ($colsOrigen as $idx => $origCol) {
            $colMapping[$origCol] = $baseCol + 1 + $idx;
        }

        foreach ($cajonesOrigen as $cajon) {
            $cajon->update([
                'mueble_id' => $muebleDestino->id,
                'columna' => $colMapping[$cajon->columna],
            ]);
        }

        // Actualizar filas y columnas del destino
        $totalCols = $muebleDestino->cajones()->distinct()->count('columna');
        $maxFilas = $muebleDestino->cajones()->max('fila') ?? 1;
        $muebleDestino->update([
            'columnas' => $totalCols,
            'filas' => max($muebleDestino->filas, $maxFilas),
        ]);

        $nombreOrigen = $mueble->nombre;
        $mueble->delete();

        return response()->json([
            'message' => "\"{$nombreOrigen}\" consolidado dentro de \"{$muebleDestino->nombre}\"",
            'mueble_destino' => $muebleDestino,
        ]);
    }

    /**
     * Move a single column from this furniture to another target furniture.
     */
    public function moverColumna(Request $request, Mueble $mueble): JsonResponse
    {
        $validated = $request->validate([
            'columna' => 'required|integer|min:1',
            'mueble_destino_id' => 'required|exists:muebles,id',
        ]);

        $col = (int) $validated['columna'];
        $destinoId = (int) $validated['mueble_destino_id'];

        if ($mueble->id === $destinoId) {
            return response()->json(['message' => 'El mueble destino debe ser diferente'], 422);
        }

        $muebleDestino = Mueble::findOrFail($destinoId);

        $cajonesColumna = Cajon::where('mueble_id', $mueble->id)->where('columna', $col)->get();
        if ($cajonesColumna->isEmpty()) {
            return response()->json(['message' => 'No hay gavetas en esa columna'], 422);
        }

        $newColDestino = ($muebleDestino->cajones()->max('columna') ?? 0) + 1;

        foreach ($cajonesColumna as $cajon) {
            $cajon->update([
                'mueble_id' => $muebleDestino->id,
                'columna' => $newColDestino,
            ]);
        }

        // Actualizar destino
        $muebleDestino->update([
            'columnas' => $muebleDestino->cajones()->distinct()->count('columna'),
            'filas' => max($muebleDestino->filas, $muebleDestino->cajones()->max('fila') ?? 1),
        ]);

        // Reindexar origen
        $restantes = Cajon::where('mueble_id', $mueble->id)->distinct()->orderBy('columna')->pluck('columna');
        if ($restantes->isEmpty()) {
            $mueble->delete();
        } else {
            $newCol = 1;
            foreach ($restantes as $oldCol) {
                if ($oldCol != $newCol) {
                    Cajon::where('mueble_id', $mueble->id)->where('columna', $oldCol)->update(['columna' => $newCol]);
                }
                $newCol++;
            }
            $mueble->update(['columnas' => count($restantes)]);
        }

        return response()->json([
            'message' => "Columna trasladada a \"{$muebleDestino->nombre}\"",
        ]);
    }
}
