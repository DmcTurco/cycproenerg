<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// CM-10: detalle de un inventario físico, una fila por cada material u
// herramienta CONTADO (si no se llenó el conteo de un ítem, no se guarda
// fila para él — igual que el Excel, que deja la columna DIFERENCIA en
// blanco si CONTEO FISICO está vacío).
//
// Se guardan `codigo`/`descripcion`/`unidad` COMO FOTO (no solo el FK) para
// que el historial de un inventario viejo se siga leyendo igual aunque el
// material/herramienta cambie de nombre o se elimine después — mismo
// criterio de "nunca perder el dato" que el resto del módulo.
//
// MATERIAL (INVENTARIO FISICO!A6:G162): stock_sistema = Material::stockActual()
// al momento del conteo; diferencia = conteo_fisico - stock_sistema;
// observacion_ubicacion es texto libre (a mano).
//
// HERRAMIENTA (INVENTARIO FISICO!A166:G265): stock_sistema es 1 si el
// sistema espera que esté en ALMACEN, 0 si espera que esté EN CAMPO
// (Herramienta::ubicacion()); conteo_fisico es 1 si efectivamente se
// encontró en el almacén al contar, 0 si no; observacion_ubicacion se
// llena automático con la ubicación que el sistema tenía registrada (no es
// campo manual para herramientas, a diferencia de los materiales).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventario_fisico_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventario_fisico_id')->constrained('inventarios_fisicos')->cascadeOnDelete();
            // MATERIAL | HERRAMIENTA.
            $table->string('tipo', 20);
            $table->foreignId('material_id')->nullable()->constrained('materiales')->nullOnDelete();
            $table->foreignId('herramienta_id')->nullable()->constrained('herramientas')->nullOnDelete();
            $table->string('codigo');
            $table->string('descripcion')->nullable();
            $table->string('unidad', 20)->nullable();
            $table->decimal('stock_sistema', 12, 2);
            $table->decimal('conteo_fisico', 12, 2);
            $table->decimal('diferencia', 12, 2);
            $table->string('observacion_ubicacion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_fisico_detalles');
    }
};
