<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * CM-1: equivalente al bloque "PARAMETROS GENERALES" de la hoja INICIO
     * del Excel de Control de Materiales (IGV, margen general sobre compra,
     * umbral de alarma de precio). Una única fila de configuración,
     * editable desde una pantalla de administración — igual que
     * parametros_control_internos.
     */
    public function up(): void
    {
        Schema::create('parametros_control_materiales', function (Blueprint $table) {
            $table->id();
            $table->decimal('igv', 5, 4)->default(0.1800)
                ->comment('INICIO!G6 — IGV usado en cotizaciones y precios de venta');
            $table->decimal('margen_general', 5, 4)->default(0.1000)
                ->comment('INICIO!G7 — margen sobre compra por defecto; cada material puede tener su propio margen (materiales.margen_pct)');
            $table->decimal('umbral_alarma_precio', 5, 4)->default(0.0500)
                ->comment('INICIO!G8 — variación mínima (%) para que un ingreso dispare la alerta "SUBIO" en vez de "ESTABLE"');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parametros_control_materiales');
    }
};
