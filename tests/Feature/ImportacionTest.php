<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ImportacionTest extends TestCase
{
    use RefreshDatabase;
    public function test_importacion_de_excel_guarda_datos()
    {
        $proveedor = \App\Models\Proveedor::create(['nombre' => 'Test']);
    
        $contenidoCsv = "REF,Brand,Barcode,Desc,Dimensions,Cat,SubCat,Price_1\n";
        $contenidoCsv .= "EG-001,Samsung,84123,Monitor 24,50x30,IT,Monitores,150.00\n";
    
        $archivoFalso = \Illuminate\Http\UploadedFile::fake()->createWithContent('test.csv', $contenidoCsv);
    
        $response = $this->post('/importar-web', [
            'proveedor_id'     => $proveedor->id,
            'codigo_proveedor' => 'lucgom_global',
            'archivo'          => $archivoFalso
        ]);
    
        $response->assertStatus(302); 
        
        $this->assertDatabaseHas('productos', [
            'proveedor_id'         => $proveedor->id,
            'referencia_proveedor' => 'EG-001',
            'marca'                => 'Samsung'
        ]);
    }
    public function test_api_consulta_filtra_por_marca_y_referencia()
    {
     
        $proveedor = \App\Models\Proveedor::create(['nombre' => 'Test API']);
        
        \App\Models\Producto::create([
            'proveedor_id'         => $proveedor->id,
            'referencia_proveedor' => 'REF-123',
            'marca'                => 'Samsung',
            'descripcion'          => 'Monitor 24'
        ]);

        \App\Models\Producto::create([
            'proveedor_id'         => $proveedor->id,
            'referencia_proveedor' => 'REF-999',
            'marca'                => 'LG',
            'descripcion'          => 'TV 50'
        ]);

        $response = $this->getJson('/api/productos?marca=Samsung');

        $response->assertStatus(200)
                 ->assertJsonFragment(['marca' => 'Samsung'])
                 ->assertJsonMissing(['marca' => 'LG']);
    }

    
}
