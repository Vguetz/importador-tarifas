<?php

return [
    'lucgom_global' => [
        'columnas' => [
            'referencia'  => 'REF',
            'marca'       => 'Brand',
            'codigo_ean'  => 'Barcode',
            'descripcion' => 'Desc',
            'dimensiones' => 'Dimensions',
            'familia'     => 'Cat',
            'subfamilia'  => 'SubCat',
        ],
        'tramos_precio' => [
            1  => 'Price_1',
            10 => 'Price_10',
            50 => 'Price_50'
        ],
        'reglas' => [
            'impuestos_incluidos' => false,
            'unidad_defecto' => 'unidad'
    ]
    ],

'industrial_parts' => [ 
        'columnas' => [
            'referencia'      => 'Part Number',
            'marca'           => 'Manufacturer',
            'codigo_ean'      => 'EAN',
            'descripcion'     => 'Product Name',
            'dimensiones'     => 'Size_mm',
            'familia'         => 'Family',
            'subfamilia'      => 'Type',
            'precio'          => 'Final_Price',
            'cantidad_minima' => 'Min_Qty',
            'unidad_medida'   => 'Unit',
            'pais_destino'    => 'Tax_Country'
        ],
        'reglas' => [
            'impuestos_incluidos' => false
        ]
    ],

    'proveedor_c' => [
    'nombre' => 'Global Logistics C',
    'columnas' => [
        'marca' => 'Brand_Name',
        'referencia' => 'SKU_ID',
        'ean' => 'EAN_Code',
        'descripcion' => 'Product_Desc',
        'dimensiones' => 'Size_Specs',
        'precio' => 'Base_Price', 
    ],
],
];
