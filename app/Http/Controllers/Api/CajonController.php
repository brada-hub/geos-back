<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cajon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CajonController extends Controller
{
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
}
