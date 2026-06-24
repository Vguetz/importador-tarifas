<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductoImpuesto extends Model
{
    protected $table = 'producto_impuestos';

    protected $fillable = [
        'producto_id',
        'pais_destino',
        'unidad_medida',
        'porcentaje'
    ];
}