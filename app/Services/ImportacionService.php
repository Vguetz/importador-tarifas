<?php

namespace App\Services;

use App\Models\Producto;
use App\Models\ProductoPrecio;
use App\Models\ProductoImpuesto;
use Rap2hpoutre\FastExcel\FastExcel;
use Illuminate\Support\Facades\Config;

class ImportacionService
{
    public function procesarArchivo($rutaArchivo, $codigoProveedor, $proveedorId)
    {
        $config = Config::get("proveedores.{$codigoProveedor}");
        $reglas = $config['reglas'] ?? [];

        if (!$config) {
            throw new \Exception("no se encontro la configuracion para el proveedor: {$codigoProveedor}");
        }

        $mapeo = $config['columnas'];

        (new FastExcel)->import($rutaArchivo, function ($fila) use ($mapeo, $config, $proveedorId, $reglas) {
            
            $referencia = $fila[$mapeo['referencia']] ?? null;
            if (!$referencia) {
                return; 
            }

            $producto = Producto::updateOrCreate(
                [
                    'proveedor_id' => $proveedorId, 
                    'referencia_proveedor' => $referencia
                ],
                [
                    'marca'       => $fila[$mapeo['marca']] ?? 'Genérica',
                    'codigo_ean'  => $fila[$mapeo['codigo_ean']] ?? null,
                    'descripcion' => $fila[$mapeo['descripcion']] ?? null,
                    'dimensiones' => $fila[$mapeo['dimensiones']] ?? null,
                    'familia'     => $fila[$mapeo['familia']] ?? null,
                    'subfamilia'  => $fila[$mapeo['subfamilia']] ?? null,
                ]
            );

        
            if (isset($config['tramos_precio'])) {
                foreach ($config['tramos_precio'] as $cantidadMinima => $columnaPrecio) {
                    $precioCrudo = $fila[$columnaPrecio] ?? null;
                    if ($precioCrudo !== null && $precioCrudo !== '') {
                        $this->registrarPrecioYTramos($producto->id, $cantidadMinima, $precioCrudo, $reglas);   
                    }
                }
            } else {
                $cantidadMinima = $fila[$mapeo['cantidad_minima']] ?? 1;
                $precioCrudo = $fila[$mapeo['precio']] ?? 0;
                $this->registrarPrecioYTramos($producto->id, $cantidadMinima, $precioCrudo, $reglas);
            }

            if (isset($mapeo['pais_destino']) && isset($fila[$mapeo['pais_destino']])) {
                ProductoImpuesto::updateOrCreate(
                    [
                        'producto_id'  => $producto->id,
                        'pais_destino' => $fila[$mapeo['pais_destino']]
                    ],
                    [
                        'unidad_medida' => $fila[$mapeo['unidad_medida']] ?? $reglas['unidad_defecto'] ?? null,
                        'porcentaje'    => $reglas['porcentaje_impuesto'] ?? 0.00
                    ]
                );
            }
        });
    }


    private function registrarPrecioYTramos($productoId, $cantidadMinima, $precioCrudo, $reglas)
    {
        $precio = (float) str_replace([',', '$', ' '], ['', '', ''], $precioCrudo);

        $impuestosIncluidos = $reglas['impuestos_incluidos'] ?? false;
        $porcentajeImpuesto = $reglas['porcentaje_impuesto'] ?? 0;

        if ($impuestosIncluidos && $porcentajeImpuesto > 0) {
            $precio = $precio / (1 + ($porcentajeImpuesto / 100));
        }

        ProductoPrecio::updateOrCreate(
            [
                'producto_id'     => $productoId,
                'cantidad_minima' => $cantidadMinima
            ],
            [
                'precio' => $precio
            ]
        );
    }
}