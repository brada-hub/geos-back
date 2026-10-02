<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cargo;
use App\Models\Sede;
use App\Models\Sexo;
use App\Models\TipoContrato;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogoController extends Controller
{
    public function getTiposContrato(): JsonResponse
    {
        return response()->json(TipoContrato::all());
    }

    public function getSexos(): JsonResponse
    {
        return response()->json(Sexo::all());
    }

    public function getCargos(): JsonResponse
    {
        return response()->json(Cargo::orderBy('nombre')->get());
    }

    public function storeCargo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:cargos,nombre',
            'descripcion' => 'nullable|string',
        ]);

        $cargo = Cargo::create($validated);

        return response()->json($cargo, 201);
    }

    public function getSedes(): JsonResponse
    {
        return response()->json(Sede::withCount('muebles')->get());
    }

    public function storeSede(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255|unique:sedes,nombre',
        ]);

        $sede = Sede::create($validated);

        return response()->json($sede, 201);
    }
}
