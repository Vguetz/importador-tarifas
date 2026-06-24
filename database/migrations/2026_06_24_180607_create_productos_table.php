<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained('proveedores')->onDelete('cascade');
            $table->string('referencia_proveedor');
            $table->string('marca');
            $table->string('codigo_ean')->nullable(); 
            $table->text('descripcion')->nullable();
            $table->string('dimensiones')->nullable();
            $table->string('familia')->nullable();
            $table->string('subfamilia')->nullable();
            $table->timestamps();
            $table->index(['marca', 'referencia_proveedor']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
