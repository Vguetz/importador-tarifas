<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ImportacionService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ImportadorController extends Controller
{
    protected $importacionService;

    public function __construct(ImportacionService $importacionService)
    {
        $this->importacionService = $importacionService;
    }

    public function importar(Request $request)  
    {
        $request->validate([
            'archivo'          => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'codigo_proveedor' => 'required|string',
            'proveedor_id'     => 'required|integer|exists:proveedores,id'
        ]);

        try {
            $archivo = $request->file('archivo');
            $rutaTemporal = $archivo->store('temp');
            $rutaAbsoluta = $request->file('archivo')->getRealPath();

           
            $this->importacionService->procesarArchivo(
                $rutaAbsoluta,
                $request->input('codigo_proveedor'),
                $request->input('proveedor_id')
            );

            Storage::delete($rutaTemporal);

            return response()->json([
                'success' => true,
                'message' => 'Archivo importado y procesado correctamente.'
            ], 200);
        } catch (\Exception $e) {
            Log::error('error en importacion: ' . $e->getMessage());

            if (isset($rutaTemporal)) {
                Storage::delete($rutaTemporal);
            }

            return response()->json([
                'success' => false,
                'message' => 'Hubo un error al procesar el archivo.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function importarWeb(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('--- NUEVO INTENTO DE SUBIDA ---');
        \Illuminate\Support\Facades\Log::info('1. Carpeta temporal según PHP.INI: ' . ini_get('upload_tmp_dir'));
        \Illuminate\Support\Facades\Log::info('2. Carpeta temporal del Sistema: ' . sys_get_temp_dir());
    
        if ($request->hasFile('archivo')) {
            $archivo = $request->file('archivo');
            \Illuminate\Support\Facades\Log::info('3. Archivo detectado: ' . $archivo->getClientOriginalName());
            \Illuminate\Support\Facades\Log::info('4. Ruta donde PHP lo dejó: ' . $archivo->getRealPath());
            \Illuminate\Support\Facades\Log::info('5. ¿Es válido?: ' . ($archivo->isValid() ? 'Sí' : 'No'));
            \Illuminate\Support\Facades\Log::info('6. Código de error interno (0 es OK): ' . $archivo->getError());
            
            if (!$archivo->isValid()) {
                \Illuminate\Support\Facades\Log::error('7. Motivo del fallo: ' . $archivo->getErrorMessage());
            }
        } else {
            \Illuminate\Support\Facades\Log::warning('ATENCIÓN: El Request llegó pero sin el archivo adjunto.');
        }
    
        $request->validate([
            'archivo'          => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'codigo_proveedor' => 'required|string',
            'proveedor_id'     => 'required|integer|exists:proveedores,id'
        ]);
    
        try {
            
            $directorio = storage_path('app/uploads');
            
         
            if (!file_exists($directorio)) {
                mkdir($directorio, 0777, true);
            }
        
            
            $nombreArchivo = time() . '_' . $request->file('archivo')->getClientOriginalName();
            $archivoMovido = $request->file('archivo')->move($directorio, $nombreArchivo);
        
           
            $this->importacionService->procesarArchivo(
                $archivoMovido->getRealPath(),
                $request->input('codigo_proveedor'),
                $request->input('proveedor_id')
            );
        
            
            @unlink($archivoMovido->getRealPath());
        
            return redirect()->back()->with('success', '¡Importación finalizada con éxito!');
        
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error($e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function consultar(Request $request)
    {
        $query = \App\Models\Producto::query();

        if ($request->has('marca')) {
            $query->where('marca', $request->input('marca'));
        }

        if ($request->has('referencia')) {
            $query->where('referencia_proveedor', $request->input('referencia'));
        }

        $productos = $query->with(['proveedor', 'precios', 'impuestos'])->paginate(50);

        return response()->json($productos);
    }

}
