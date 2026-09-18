<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// CM-3: Ingresos (kardex de entradas de materiales al almacén).
// Equivalente a la hoja INGRESOS del Excel: una fila por cada llegada.
//
// Se guarda material_id (FK) en vez de repetir el código como texto libre
// (el Excel lo hacía porque el código ES el dato; acá ya existe la tabla
// materiales, así que se referencia). DESCRIPCION y UNID. del Excel no se
// guardan: son columnas de fórmula (INDEX/MATCH contra CATALOGO) que acá se
// resuelven con la relación material() al mostrar.
//
// precio_compra es nullable a propósito (columna "opcional" en el Excel:
// "anotalo solo si quieres control de precio"). La ALERTA PRECIO no se
// guarda: es un cálculo (ver Ingreso::alertaPrecio()), igual que el resto
// del kardex de este módulo.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingresos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materiales')->cascadeOnDelete();
            $table->date('fecha');
            $table->decimal('cantidad', 12, 2);
            $table->string('proveedor')->nullable();
            $table->string('guia_factura', 60)->nullable();
            $table->decimal('precio_compra', 10, 2)->nullable();
            $table->text('observacion')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingresos');
    }
};
