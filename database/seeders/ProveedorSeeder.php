<?php

namespace Database\Seeders;

use App\Models\Proveedor;
use Illuminate\Database\Seeder;

class ProveedorSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('proveedores') as $codigo => $datos) {
            Proveedor::updateOrCreate(
                ['codigo' => $codigo],
                ['nombre' => $datos['nombre'] ?? $codigo]
            );
        }
    }
}