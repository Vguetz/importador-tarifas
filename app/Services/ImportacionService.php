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

        if (!$config) {
            throw new \Exception("no se encontro la configuracion para el proveedor: {$codigoProveedor}");
        }

        $reglas = $config['reglas'] ?? [];
        $mapeo  = $config['columnas'];

        (new FastExcel)->import($rutaArchivo, function ($fila) use ($mapeo, $config, $proveedorId, $reglas) {

            $referencia = $this->valor($fila, $mapeo, 'referencia');
            if (!$referencia) {
                return;
            }

            $producto = Producto::updateOrCreate(
                [
                    'proveedor_id'         => $proveedorId,
                    'referencia_proveedor' => $referencia
                ],
                [
                    'marca'       => $this->valor($fila, $mapeo, 'marca', 'Genérica'),
                    'codigo_ean'  => $this->valor($fila, $mapeo, 'codigo_ean'),
                    'descripcion' => $this->valor($fila, $mapeo, 'descripcion'),
                    'dimensiones' => $this->valor($fila, $mapeo, 'dimensiones'),
                    'familia'     => $this->valor($fila, $mapeo, 'familia'),
                    'subfamilia'  => $this->valor($fila, $mapeo, 'subfamilia'),
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
                $cantidadMinima = $this->valor($fila, $mapeo, 'cantidad_minima', 1);
                $precioCrudo    = $this->valor($fila, $mapeo, 'precio', 0);
                $this->registrarPrecioYTramos($producto->id, $cantidadMinima, $precioCrudo, $reglas);
            }

            $paisDestino = $this->valor($fila, $mapeo, 'pais_destino');
            if ($paisDestino !== null) {
                ProductoImpuesto::updateOrCreate(
                    [
                        'producto_id'  => $producto->id,
                        'pais_destino' => $paisDestino
                    ],
                    [
                        'unidad_medida' => $this->valor($fila, $mapeo, 'unidad_medida', $reglas['unidad_defecto'] ?? null),
                        'porcentaje'    => $reglas['porcentaje_impuesto'] ?? 0.00
                    ]
                );
            }
        });
    }


    private function valor($fila, $mapeo, $campo, $default = null)
    {
        if (!isset($mapeo[$campo])) {
            return $default;
        }

        return $fila[$mapeo[$campo]] ?? $default;
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