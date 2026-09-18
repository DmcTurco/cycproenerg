<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * CM-1: equivalente al bloque de HERRAMIENTAS de la hoja CATALOGO
     * (filas 164+ del Excel, código HER-### autogenerado). El responsable
     * actual y la ubicación (ALMACEN/EN CAMPO/PERDIDA) NO se guardan aquí:
     * ambas son fórmulas en el Excel (columnas I y J), calculadas desde
     * `entregas` (CM-7) — este modelo solo guarda la ficha de la
     * herramienta en sí, igual que el Excel solo guarda a mano A-H.
     */
    public function up(): void
    {
        Schema::create('herramientas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique()
                ->comment('CATALOGO!B164+ — "HER-###" autogenerado (ver Herramienta::siguienteCodigo())');
            $table->string('descripcion');
            $table->string('marca_modelo')->nullable();
            $table->string('numero_serie')->nullable();
            $table->date('fecha_compra')->nullable();
            $table->decimal('precio', 10, 2)->nullable();
            $table->string('estado', 20)->default('OPERATIVA')
                ->comment('OPERATIVA, PERDIDA, ... (CATALOGO!H164+) — si es PERDIDA, la ubicación calculada (CM-7) lo refleja sin importar ENTREGAS');
            $table->text('observacion')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('herramientas');
    }
};
