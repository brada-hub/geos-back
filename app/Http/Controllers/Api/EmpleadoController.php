<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmpleadoRequest;
use App\Http\Requests\UpdateEmpleadoUbicacionRequest;
use App\Models\Empleado;
use App\Models\Movimiento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmpleadoController extends Controller
{
    public function store(StoreEmpleadoRequest $request): JsonResponse
    {
        $empleado = Empleado::create($request->validated());

        return response()->json([
            'message' => 'Expediente registrado correctamente',
            'empleado' => $empleado
        ], 201);
    }

    public function updateUbicacion(UpdateEmpleadoUbicacionRequest $request, Empleado $empleado): JsonResponse
    {
        $oldCajonId = $empleado->cajon_id;

        $empleado->update([
            'cajon_id' => $request->cajon_id
        ]);

        // Registrar movimiento automático
        Movimiento::create([
            'empleado_id' => $empleado->id,
            'cajon_origen_id' => $oldCajonId,
            'cajon_destino_id' => $request->cajon_id,
            'comentario' => 'Movimiento por arrastre',
        ]);

        return response()->json([
            'message' => 'Ubicación actualizada correctamente',
            'empleado' => $empleado
        ]);
    }

    public function index(): JsonResponse
    {
        return response()->json(Empleado::all());
    }

    public function getMovimientos(Empleado $empleado): JsonResponse
    {
        return response()->json($empleado->movimientos);
    }

    public function storeMovimiento(Request $request, Empleado $empleado): JsonResponse
    {
        $validated = $request->validate([
            'comentario' => 'nullable|string',
            'estado_nuevo' => 'nullable|integer|in:0,1,2', // 0=presente, 1=ausente, 2=prestado
        ]);

        $estadoAnterior = $empleado->estado;

        if (isset($validated['estado_nuevo'])) {
            $empleado->update(['estado' => $validated['estado_nuevo']]);
        }

        Movimiento::create([
            'empleado_id' => $empleado->id,
            'comentario' => $validated['comentario'] ?? null,
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => $validated['estado_nuevo'] ?? null,
        ]);

        return response()->json(['message' => 'Movimiento registrado']);
    }
}
