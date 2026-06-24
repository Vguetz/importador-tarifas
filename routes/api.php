<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ImportadorController;

// el endpoint para importar un archivo es POST http://localhost:8000/api/importar
Route::post('/importar', [ImportadorController::class, 'importar']);

// el endpoint para consultar productos es GET http://localhost:8000/api/productos

Route::get('/api/productos', [\App\Http\Controllers\Api\ImportadorController::class, 'consultar']);