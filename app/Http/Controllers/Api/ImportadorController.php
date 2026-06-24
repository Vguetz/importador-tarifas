<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ImportacionService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\Proveedor;

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
            Log::error($e->getMessage());
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

    public function mostrarFormulario()
    {
        return view('importar', [
            'proveedores' => Proveedor::orderBy('nombre')->get(),
            'codigos'     => array_keys(config('proveedores')),
        ]);
    }
}
