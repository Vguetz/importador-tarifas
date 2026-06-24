<?php

namespace Database\Seeders;

use App\Models\Proveedor;
use Illuminate\Database\Seeder;

class ProveedorSeeder extends Seeder
{
    public function run(): void
    {
        $proveedores = [
            ['id' => 1, 'nombre' => 'LucGomGlobal'],
            ['id' => 2, 'nombre' => 'Componentes Industriales S.A.'],
            ['id' => 3, 'nombre' => 'Global Logistics C'],
        ];

        foreach ($proveedores as $proveedor) {
            Proveedor::updateOrCreate(
                ['id' => $proveedor['id']],
                ['nombre' => $proveedor['nombre']]
            );
        }
    }
}