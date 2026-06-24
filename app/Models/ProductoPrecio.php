<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductoPrecio extends Model
{
    protected $table = 'producto_precios';

    protected $fillable = [
        'producto_id',
        'cantidad_minima',
        'precio'
    ];
}