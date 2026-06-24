<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ImportacionService;

class ImportarTarifasCommand extends Command
    {
    protected $signature = 'importar:excel {ruta_archivo} {codigo_proveedor} {proveedor_id}';

    protected $description = 'Importa un archivo Excel de tarifas de proveedores directamente a la base de datos';

    public function handle(ImportacionService $importacionService)
    {
        $rutaAbsoluta = $this->argument('ruta_archivo');
        $codigoProveedor = $this->argument('codigo_proveedor');
        $proveedorId = $this->argument('proveedor_id');

        if (!file_exists($rutaAbsoluta)) {
            $this->error("El archivo no existe en la ruta: {$rutaAbsoluta}");
            return 1;
        }

        $this->info("Iniciando importación para el proveedor {$codigoProveedor}...");

        try {   
            $importacionService->procesarArchivo($rutaAbsoluta, $codigoProveedor, $proveedorId);
            
            $this->info('¡Importación finalizada con éxito! Todos los datos están en la base de datos.');
            return 0;

        } catch (\Exception $e) {
            $this->error('Ocurrió un error procesando el Excel: ' . $e->getMessage());
            return 1;
        }
    }
}