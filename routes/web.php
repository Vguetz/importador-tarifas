<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ImportadorController;


Route::get('/', function () {
    return view('importar');
});
Route::post('/importar-web', [ImportadorController::class, 'importarWeb'])->name('importar.web');

Route::get('/api/productos', [\App\Http\Controllers\Api\ImportadorController::class, 'consultar']);