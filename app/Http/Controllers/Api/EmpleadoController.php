<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmpleadoRequest;
use App\Http\Requests\UpdateEmpleadoUbicacionRequest;
use App\Models\Cajon;
use App\Models\Cargo;
use App\Models\Empleado;
use App\Models\Movimiento;
use App\Models\Sexo;
use App\Models\TipoContrato;
use App\Models\Sede;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmpleadoController extends Controller
{
    public function store(StoreEmpleadoRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Validar regla de admisión de la gaveta de destino
        if (!empty($data['cajon_id'])) {
            $cajon = Cajon::find($data['cajon_id']);
            if ($cajon) {
                if (!$cajon->admiteTipoContrato($data['tipo_contrato_id'] ?? null)) {
                    $permitidos = $cajon->tiposContratoPermitidos()->pluck('nombre')->join(', ');
                    return response()->json([
                        'message' => "La gaveta seleccionada solo admite expedientes con régimen: {$permitidos}."
                    ], 422);
                }
                if (!$cajon->admiteSede($data['sede_id'] ?? null)) {
                    $sedesPermitidas = $cajon->sedesPermitidas()->pluck('nombre')->join(', ');
                    return response()->json([
                        'message' => "La gaveta seleccionada no admite expedientes de esta sede. Solo admite: {$sedesPermitidas}."
                    ], 422);
                }
                if (!$cajon->admiteApellido($data['primer_apellido'] ?? null)) {
                    $rango = "{$cajon->apellido_desde} - {$cajon->apellido_hasta}";
                    return response()->json([
                        'message' => "El apellido '{$data['primer_apellido']}' no entra en el rango alfabético de la gaveta ({$rango})."
                    ], 422);
                }
            }
        }

        // Si se envió cargo_nombre y no cargo_id, buscar o crear el cargo
        if (empty($data['cargo_id']) && !empty($data['cargo_nombre'])) {
            $cargo = Cargo::firstOrCreate(['nombre' => trim($data['cargo_nombre'])]);
            $data['cargo_id'] = $cargo->id;
            $data['cargo'] = $cargo->nombre;
        } elseif (!empty($data['cargo_id'])) {
            $cargo = Cargo::find($data['cargo_id']);
            if ($cargo) {
                $data['cargo'] = $cargo->nombre;
            }
        }

        if (!empty($data['sexo_id'])) {
            $sexo = Sexo::find($data['sexo_id']);
            if ($sexo) {
                $data['sexo'] = $sexo->codigo;
            }
        }

        $empleado = Empleado::create($data);

        // Registrar movimiento inicial de ingreso
        Movimiento::create([
            'empleado_id' => $empleado->id,
            'tipo_movimiento' => 'general',
            'cajon_destino_id' => $empleado->cajon_id,
            'tipo_contrato_nuevo_id' => $empleado->tipo_contrato_id,
            'cargo_nuevo' => $empleado->cargo,
            'comentario' => 'Alta de expediente en el sistema',
        ]);

        $empleado->load(['cargoRelacion', 'tipoContrato', 'sexoRelacion', 'cajon.mueble.sede']);

        return response()->json([
            'message' => 'Expediente registrado correctamente',
            'empleado' => $empleado
        ], 201);
    }

    public function update(Request $request, Empleado $empleado): JsonResponse
    {
        $validated = $request->validate([
            'nombres' => 'sometimes|required|string|max:150',
            'primer_apellido' => 'sometimes|required|string|max:100',
            'segundo_apellido' => 'nullable|string|max:100',
            'documento_identidad' => "sometimes|required|string|max:30|unique:empleados,documento_identidad,{$empleado->id}",
            'fecha_nacimiento' => 'nullable|date',
            'sexo_id' => 'sometimes|required|exists:sexos,id',
            'tipo_contrato_id' => 'sometimes|required|exists:tipos_contrato,id',
            'cargo_id' => 'nullable|exists:cargos,id',
            'cargo_nombre' => 'nullable|string|max:150',
            'sede_id' => 'sometimes|required|exists:sedes,id',
            'cajon_id' => 'nullable|exists:cajones,id',
            'codigo_archivo' => "sometimes|required|string|unique:empleados,codigo_archivo,{$empleado->id}",
            'numero_unico' => 'nullable|string|max:20',
            'comentario' => 'nullable|string',
        ]);

        // Validar regla si se cambia de cajón, contrato o sede
        $nuevoCajonId = array_key_exists('cajon_id', $validated) ? $validated['cajon_id'] : $empleado->cajon_id;
        $nuevoContratoId = $validated['tipo_contrato_id'] ?? $empleado->tipo_contrato_id;
        $nuevaSedeId = $validated['sede_id'] ?? $empleado->sede_id;

        if ($nuevoCajonId) {
            $cajonDestino = Cajon::find($nuevoCajonId);
            if ($cajonDestino) {
                if (!$cajonDestino->admiteTipoContrato($nuevoContratoId)) {
                    $permitidos = $cajonDestino->tiposContratoPermitidos()->pluck('nombre')->join(', ');
                    return response()->json([
                        'message' => "La gaveta destino no admite expedientes con contrato: {$permitidos}."
                    ], 422);
                }
                if (!$cajonDestino->admiteSede($nuevaSedeId)) {
                    $sedesPermitidas = $cajonDestino->sedesPermitidas()->pluck('nombre')->join(', ');
                    return response()->json([
                        'message' => "La gaveta destino no admite expedientes de esta sede. Solo admite: {$sedesPermitidas}."
                    ], 422);
                }
            }
        }

        // Manejar cargo si es nuevo
        if (empty($validated['cargo_id']) && !empty($validated['cargo_nombre'])) {
            $cargo = Cargo::firstOrCreate(['nombre' => trim($validated['cargo_nombre'])]);
            $validated['cargo_id'] = $cargo->id;
            $validated['cargo'] = $cargo->nombre;
        } elseif (!empty($validated['cargo_id'])) {
            $cargo = Cargo::find($validated['cargo_id']);
            if ($cargo) {
                $validated['cargo'] = $cargo->nombre;
            }
        }

        if (!empty($validated['sexo_id'])) {
            $sexo = Sexo::find($validated['sexo_id']);
            if ($sexo) {
                $validated['sexo'] = $sexo->codigo;
            }
        }

        // Detectar y auditar cambios
        $antiguoContratoId = $empleado->tipo_contrato_id;
        $antiguoCargo = $empleado->cargo;
        $antiguoCajonId = $empleado->cajon_id;
        $antiguaSedeId = $empleado->sede_id;

        $cambioContrato = isset($validated['tipo_contrato_id']) && $validated['tipo_contrato_id'] != $antiguoContratoId;
        $cambioCargo = isset($validated['cargo']) && $validated['cargo'] != $antiguoCargo;
        $cambioUbicacion = array_key_exists('cajon_id', $validated) && $validated['cajon_id'] != $antiguoCajonId;
        $cambioSede = isset($validated['sede_id']) && $validated['sede_id'] != $antiguaSedeId;

        $empleado->update($validated);

        // Registro de auditoría en movimientos
        if ($cambioContrato) {
            $tcViejo = TipoContrato::find($antiguoContratoId);
            $tcNuevo = TipoContrato::find($validated['tipo_contrato_id']);
            Movimiento::create([
                'empleado_id' => $empleado->id,
                'tipo_movimiento' => 'contrato',
                'tipo_contrato_anterior_id' => $antiguoContratoId,
                'tipo_contrato_nuevo_id' => $validated['tipo_contrato_id'],
                'comentario' => "Cambio de Régimen Contractual: \"{$tcViejo?->nombre}\" ➔ \"{$tcNuevo?->nombre}\"",
            ]);
        }

        if ($cambioCargo) {
            Movimiento::create([
                'empleado_id' => $empleado->id,
                'tipo_movimiento' => 'cargo',
                'cargo_anterior' => $antiguoCargo,
                'cargo_nuevo' => $validated['cargo'],
                'comentario' => "Cambio de Cargo: \"{$antiguoCargo}\" ➔ \"{$validated['cargo']}\"",
            ]);
        }

        if ($cambioSede) {
            $sedeVieja = Sede::find($antiguaSedeId);
            $sedeNueva = Sede::find($validated['sede_id']);
            Movimiento::create([
                'empleado_id' => $empleado->id,
                'tipo_movimiento' => 'general',
                'comentario' => "Traslado de Sede: \"{$sedeVieja?->nombre}\" ➔ \"{$sedeNueva?->nombre}\"",
            ]);
        }

        if ($cambioUbicacion) {
            Movimiento::create([
                'empleado_id' => $empleado->id,
                'tipo_movimiento' => 'ubicacion',
                'cajon_origen_id' => $antiguoCajonId,
                'cajon_destino_id' => $validated['cajon_id'],
                'comentario' => $validated['cajon_id'] ? ($validated['comentario'] ?? 'Reubicación manual de expediente') : 'Desasignado a gaveta virtual',
            ]);
        }

        $empleado->load(['cargoRelacion', 'tipoContrato', 'sexoRelacion', 'sede', 'cajon.mueble.sede']);

        return response()->json([
            'message' => 'Información de personal actualizada correctamente',
            'empleado' => $empleado
        ]);
    }

    public function destroy(Empleado $empleado): JsonResponse
    {
        $nombre = $empleado->nombre_completo;
        $empleado->delete();

        return response()->json([
            'message' => "Expediente de \"{$nombre}\" eliminado correctamente."
        ]);
    }

    public function updateUbicacion(UpdateEmpleadoUbicacionRequest $request, Empleado $empleado): JsonResponse
    {
        $newCajonId = $request->cajon_id;

        if ($newCajonId) {
            $destCajon = Cajon::find($newCajonId);
            if ($destCajon) {
                if (!$destCajon->admiteTipoContrato($empleado->tipo_contrato_id)) {
                    $permitidos = $destCajon->tiposContratoPermitidos()->pluck('nombre')->join(', ');
                    return response()->json([
                        'message' => "Esta gaveta no admite el régimen de contrato del expediente ({$empleado->tipoContrato?->nombre}). Solo admite: {$permitidos}."
                    ], 422);
                }
                if (!$destCajon->admiteSede($empleado->sede_id)) {
                    $sedesPermitidas = $destCajon->sedesPermitidas()->pluck('nombre')->join(', ');
                    return response()->json([
                        'message' => "Esta gaveta no admite expedientes de la sede ({$empleado->sede?->nombre}). Solo admite: {$sedesPermitidas}."
                    ], 422);
                }
                if (!$destCajon->admiteApellido($empleado->primer_apellido)) {
                    $rango = "{$destCajon->apellido_desde} - {$destCajon->apellido_hasta}";
                    return response()->json([
                        'message' => "El apellido '{$empleado->primer_apellido}' no entra en el rango alfabético de la gaveta ({$rango})."
                    ], 422);
                }
            }
        }

        $oldCajonId = $empleado->cajon_id;

        $empleado->update([
            'cajon_id' => $newCajonId
        ]);

        // Registrar movimiento automático
        $comentario = $newCajonId
            ? ($oldCajonId ? 'Movimiento por arrastre entre gavetas' : 'Asignación a gaveta física desde gaveta virtual')
            : 'Desasignado y movido a gaveta virtual / expedientes sin asignar';

        Movimiento::create([
            'empleado_id' => $empleado->id,
            'tipo_movimiento' => 'ubicacion',
            'cajon_origen_id' => $oldCajonId,
            'cajon_destino_id' => $newCajonId,
            'comentario' => $comentario,
        ]);

        $empleado->load(['cargoRelacion', 'tipoContrato', 'sexoRelacion', 'sede', 'cajon.mueble.sede']);

        return response()->json([
            'message' => 'Ubicación actualizada correctamente',
            'empleado' => $empleado
        ]);
    }

    public function index(): JsonResponse
    {
        return response()->json(
            Empleado::with(['cargoRelacion', 'tipoContrato', 'sexoRelacion', 'sede', 'cajon.mueble.sede'])
                ->orderBy('primer_apellido')
                ->orderBy('segundo_apellido')
                ->orderBy('nombres')
                ->get()
        );
    }

    public function sinAsignar(): JsonResponse
    {
        return response()->json(
            Empleado::whereNull('cajon_id')
                ->with(['cargoRelacion', 'tipoContrato', 'sexoRelacion', 'sede'])
                ->orderBy('primer_apellido')
                ->orderBy('segundo_apellido')
                ->orderBy('nombres')
                ->get()
        );
    }

    public function getMovimientos(Empleado $empleado): JsonResponse
    {
        return response()->json(
            $empleado->movimientos()
                ->with(['tipoContratoAnterior', 'tipoContratoNuevo', 'cajonOrigen.mueble.sede', 'cajonDestino.mueble.sede'])
                ->get()
        );
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
            'tipo_movimiento' => 'estado',
            'comentario' => $validated['comentario'] ?? null,
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => $validated['estado_nuevo'] ?? null,
        ]);

        return response()->json(['message' => 'Movimiento registrado']);
    }

    public function importarMasivo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'filas' => 'required|array|min:1',
        ]);

        $sedes = Sede::all();
        $tiposContrato = TipoContrato::all();
        $sexos = Sexo::all();

        $importados = 0;
        $actualizados = 0;
        $errores = [];

        foreach ($validated['filas'] as $idx => $fila) {
            try {
                $rawNombre = trim($fila['apellidos_nombres'] ?? $fila['nombre_completo'] ?? '');
                $ci = trim($fila['ci'] ?? $fila['documento_identidad'] ?? '');
                $exp = trim($fila['exp'] ?? '');
                $rawSexo = trim($fila['sexo'] ?? '');
                $rawFecha = trim($fila['fecha_nacimiento'] ?? '');
                $rawCargo = trim($fila['cargo'] ?? $fila['cargo_ocupacion'] ?? 'SIN CARGO');
                $rawSede = trim($fila['sede'] ?? '');
                $rawContrato = trim($fila['tipo_contrato'] ?? '');
                $numeroUnico = trim($fila['numero'] ?? $fila['n_'] ?? ($idx + 1));

                if (empty($rawNombre) && empty($ci)) {
                    continue;
                }

                // Armar documento de identidad
                $docIdentidad = $ci;
                if (!empty($exp) && stripos($ci, $exp) === false) {
                    $docIdentidad = $ci . ' ' . $exp;
                }

                if (empty($docIdentidad)) {
                    $errores[] = "Fila " . ($idx + 1) . ": Documento de identidad vacío.";
                    continue;
                }

                // Descomponer Apellidos y Nombres (Formato: Paterno Materno Nombres)
                $nombres = $fila['nombres'] ?? '';
                $pApellido = $fila['primer_apellido'] ?? '';
                $sApellido = $fila['segundo_apellido'] ?? '';

                if (empty($nombres) || empty($pApellido)) {
                    $tokens = array_values(array_filter(explode(' ', preg_replace('/\s+/', ' ', $rawNombre))));
                    $countTokens = count($tokens);
                    if ($countTokens === 1) {
                        $pApellido = $tokens[0];
                        $nombres = $tokens[0];
                    } elseif ($countTokens === 2) {
                        $pApellido = $tokens[0];
                        $nombres = $tokens[1];
                    } elseif ($countTokens === 3) {
                        $pApellido = $tokens[0];
                        $sApellido = $tokens[1];
                        $nombres = $tokens[2];
                    } elseif ($countTokens >= 4) {
                        $pApellido = $tokens[0];
                        $sApellido = $tokens[1];
                        $nombres = implode(' ', array_slice($tokens, 2));
                    }
                }

                // Resolver Sexo (3FN)
                $sexoId = 1; // Default Masculino
                $sexoUpper = strtoupper($rawSexo);
                if (str_starts_with($sexoUpper, 'F') || str_contains($sexoUpper, 'FEM') || str_contains($sexoUpper, 'MUJ')) {
                    $sexoObj = $sexos->firstWhere('codigo', 'F');
                    $sexoId = $sexoObj ? $sexoObj->id : 2;
                } else {
                    $sexoObj = $sexos->firstWhere('codigo', 'M');
                    $sexoId = $sexoObj ? $sexoObj->id : 1;
                }

                // Resolver Sede (3FN)
                $sedeId = null;
                $sedeNorm = mb_strtoupper(preg_replace('/[^A-Za-z0-9]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $rawSede)));
                foreach ($sedes as $s) {
                    $sNorm = mb_strtoupper(preg_replace('/[^A-Za-z0-9]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $s->nombre)));
                    if (!empty($sedeNorm) && (str_contains($sNorm, $sedeNorm) || str_contains($sedeNorm, $sNorm))) {
                        $sedeId = $s->id;
                        break;
                    }
                }
                if (!$sedeId) {
                    // Fallback a primera sede o crearla
                    $firstSede = $sedes->first();
                    $sedeId = $firstSede ? $firstSede->id : Sede::create(['nombre' => $rawSede ?: 'SEDE CENTRAL'])->id;
                }

                // Resolver Tipo de Contrato (3FN)
                $contratoUpper = mb_strtoupper($rawContrato);
                $tipoContratoId = 3; // Default Indefinido
                if (str_contains($contratoUpper, 'SERV') || str_contains($contratoUpper, 'CONSULT')) {
                    $cObj = $tiposContrato->firstWhere('codigo', 'SERVICIOS');
                    $tipoContratoId = $cObj ? $cObj->id : 1;
                } elseif (str_contains($contratoUpper, 'FIJO') || str_contains($contratoUpper, 'PLAZO')) {
                    $cObj = $tiposContrato->firstWhere('codigo', 'PLAZO_FIJO');
                    $tipoContratoId = $cObj ? $cObj->id : 2;
                } else {
                    $cObj = $tiposContrato->firstWhere('codigo', 'INDEFINIDO');
                    $tipoContratoId = $cObj ? $cObj->id : 3;
                }

                // Resolver Cargo (3FN)
                $cargoNombre = trim($rawCargo) ?: 'PERSONAL';
                $cargo = Cargo::firstOrCreate(['nombre' => $cargoNombre]);

                // Resolver Fecha de Nacimiento
                $fechaNac = null;
                if (!empty($rawFecha)) {
                    // Si viene como número serial de Excel (ej: 31434)
                    if (is_numeric($rawFecha) && $rawFecha > 1000) {
                        $unixTime = ($rawFecha - 25569) * 86400;
                        $fechaNac = gmdate('Y-m-d', $unixTime);
                    } else {
                        // Formatos comunes: d/m/Y, Y-m-d, d-m-Y
                        $rawFecha = str_replace('/', '-', $rawFecha);
                        $time = strtotime($rawFecha);
                        if ($time) {
                            $fechaNac = date('Y-m-d', $time);
                        }
                    }
                }

                // Código de Archivo único
                $sedePrefijo = substr(preg_replace('/[^A-Za-z]/', '', $rawSede) ?: 'EXP', 0, 3);
                $ciClean = preg_replace('/[^0-9]/', '', $ci) ?: ($idx + 1);
                $codigoArchivo = $fila['codigo_archivo'] ?? ('EXP-' . strtoupper($sedePrefijo) . '-' . $ciClean);

                // Verificar si ya existe por Documento de Identidad
                $empleado = Empleado::where('documento_identidad', $docIdentidad)->first();

                if ($empleado) {
                    $empleado->update([
                        'nombres' => $nombres,
                        'primer_apellido' => $pApellido,
                        'segundo_apellido' => $sApellido ?: null,
                        'fecha_nacimiento' => $fechaNac ?: $empleado->fecha_nacimiento,
                        'sexo_id' => $sexoId,
                        'sexo' => $sexoId === 2 ? 'F' : 'M',
                        'cargo_id' => $cargo->id,
                        'cargo' => $cargo->nombre,
                        'tipo_contrato_id' => $tipoContratoId,
                        'sede_id' => $sedeId,
                        'numero_unico' => (string)$numeroUnico,
                    ]);
                    $actualizados++;
                } else {
                    // Asegurar código de archivo único
                    if (Empleado::where('codigo_archivo', $codigoArchivo)->exists()) {
                        $codigoArchivo .= '-' . uniqid();
                    }

                    $nuevoEmpleado = Empleado::create([
                        'codigo_archivo' => $codigoArchivo,
                        'numero_unico' => (string)$numeroUnico,
                        'nombres' => $nombres,
                        'primer_apellido' => $pApellido,
                        'segundo_apellido' => $sApellido ?: null,
                        'documento_identidad' => $docIdentidad,
                        'fecha_nacimiento' => $fechaNac,
                        'sexo_id' => $sexoId,
                        'sexo' => $sexoId === 2 ? 'F' : 'M',
                        'cargo_id' => $cargo->id,
                        'cargo' => $cargo->nombre,
                        'tipo_contrato_id' => $tipoContratoId,
                        'sede_id' => $sedeId,
                        'cajon_id' => null, // Va directo a Gaveta Virtual
                        'estado' => 0,
                    ]);

                    Movimiento::create([
                        'empleado_id' => $nuevoEmpleado->id,
                        'tipo_movimiento' => 'general',
                        'cajon_destino_id' => null,
                        'tipo_contrato_nuevo_id' => $nuevoEmpleado->tipo_contrato_id,
                        'cargo_nuevo' => $nuevoEmpleado->cargo,
                        'comentario' => 'Importado masivamente desde Excel (Gaveta Virtual)',
                    ]);

                    $importados++;
                }
            } catch (\Exception $e) {
                $errores[] = "Fila " . ($idx + 1) . ": " . $e->getMessage();
            }
        }

        return response()->json([
            'message' => "Proceso completado: {$importados} registros creados, {$actualizados} actualizados.",
            'importados' => $importados,
            'actualizados' => $actualizados,
            'errores' => $errores,
        ]);
    }
}

