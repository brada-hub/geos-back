<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmpleadoController;
use App\Http\Controllers\Api\MuebleController;
use App\Http\Controllers\Api\CajonController;
use App\Http\Controllers\Api\CatalogoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Rutas Públicas (Con Rate Limiting estricto anti fuerza bruta)
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');

// Rutas Protegidas por Autenticación Sanctum
Route::middleware(['auth:sanctum', 'throttle:180,1'])->group(function () {
    // Sesión
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Catálogos Normalizados (3FN)
    Route::get('/catalogos/tipos-contrato', [CatalogoController::class, 'getTiposContrato']);
    Route::get('/catalogos/sexos', [CatalogoController::class, 'getSexos']);
    Route::get('/catalogos/cargos', [CatalogoController::class, 'getCargos']);
    Route::post('/catalogos/cargos', [CatalogoController::class, 'storeCargo']);
    Route::get('/catalogos/sedes', [CatalogoController::class, 'getSedes']);
    Route::post('/catalogos/sedes', [CatalogoController::class, 'storeSede']);

    // Muebles y Archivadores
    Route::get('/muebles', [MuebleController::class, 'index']);
    Route::post('/muebles', [MuebleController::class, 'store']);
    Route::patch('/muebles/{mueble}', [MuebleController::class, 'update']);
    Route::delete('/muebles/{mueble}', [MuebleController::class, 'destroy']);
    Route::post('/muebles/{mueble}/extraer-columna', [MuebleController::class, 'extraerColumna']);
    Route::post('/muebles/{mueble}/desacoplar', [MuebleController::class, 'desacoplar']);
    Route::post('/muebles/{mueble}/fusionar', [MuebleController::class, 'fusionar']);
    Route::post('/muebles/{mueble}/mover-columna', [MuebleController::class, 'moverColumna']);

    // Cajones y Gavetas
    Route::patch('/cajones/{cajon}', [CajonController::class, 'update']);
    Route::patch('/cajones/{cajon}/reglas', [CajonController::class, 'updateReglas']);
    Route::post('/cajones/{cajon}/sincronizar-automatico', [CajonController::class, 'sincronizarAutomatico']);
    Route::post('/cajones/{cajon}/asignar-por-sede', [CajonController::class, 'asignarPorSede']);
    Route::post('/muebles/{mueble}/cajones', [CajonController::class, 'store']);
    Route::delete('/cajones/{cajon}', [CajonController::class, 'destroy']);
    Route::post('/cajones/{cajon}/mover', [CajonController::class, 'mover']);

    // Empleados y Kardex
    Route::get('/empleados', [EmpleadoController::class, 'index']);
    Route::get('/empleados/sin-asignar', [EmpleadoController::class, 'sinAsignar']);
    Route::post('/empleados/importar-masivo', [EmpleadoController::class, 'importarMasivo']);
    Route::post('/empleados', [EmpleadoController::class, 'store']);
    Route::patch('/empleados/{empleado}', [EmpleadoController::class, 'update']);
    Route::delete('/empleados/{empleado}', [EmpleadoController::class, 'destroy']);
    Route::patch('/empleados/{empleado}/ubicacion', [EmpleadoController::class, 'updateUbicacion']);
    Route::get('/empleados/{empleado}/movimientos', [EmpleadoController::class, 'getMovimientos']);
    Route::post('/empleados/{empleado}/movimientos', [EmpleadoController::class, 'storeMovimiento']);
});
