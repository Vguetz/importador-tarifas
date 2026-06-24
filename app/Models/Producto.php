<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $fillable = ['proveedor_id', 'referencia_proveedor', 'marca', 'codigo_ean', 'descripcion', 'dimensiones', 'familia', 'subfamilia'];

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }
    public function precios()
    {
        return $this->hasMany(ProductoPrecio::class);
    }

    public function impuestos()
    {
        return $this->hasMany(ProductoImpuesto::class);
    }
}