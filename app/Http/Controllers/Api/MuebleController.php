<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMuebleRequest;
use App\Http\Resources\MuebleResource;
use App\Models\Cajon;
use App\Models\Mueble;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MuebleController extends Controller
{
    /**
     * Get all furniture with nested drawers and employees.
     */
    public function index(): AnonymousResourceCollection
    {
        $muebles = Mueble::with(['cajones.empleados'])->get();
        return MuebleResource::collection($muebles);
    }

    /**
     * Store a new furniture and generate its drawers.
     */
    public function store(StoreMuebleRequest $request): JsonResponse
    {
        $mueble = Mueble::create($request->validated());

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
}
