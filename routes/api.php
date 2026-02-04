<?php

use App\Http\Controllers\Api\EmpleadoController;
use App\Http\Controllers\Api\MuebleController;
use App\Http\Controllers\Api\CajonController;
use Illuminate\Support\Facades\Route;

Route::get('/muebles', [MuebleController::class, 'index']);
Route::post('/muebles', [MuebleController::class, 'store']);
Route::get('/empleados', [EmpleadoController::class, 'index']);
Route::post('/empleados', [EmpleadoController::class, 'store']);
Route::patch('/empleados/{empleado}/ubicacion', [EmpleadoController::class, 'updateUbicacion']);
Route::get('/empleados/{empleado}/movimientos', [EmpleadoController::class, 'getMovimientos']);
Route::post('/empleados/{empleado}/movimientos', [EmpleadoController::class, 'storeMovimiento']);
Route::patch('/cajones/{cajon}', [CajonController::class, 'update']);
