<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// CM-2: Cuadrillas (Control de Materiales). Equivalente a la tabla
// "REGISTRO DE CUADRILLAS" dentro de RESUMEN del Excel
// CONTROL_MATERIALES_CC_08-2026 rev 02.xlsm.
//
// Es gente DISTINTA de la tabla `tecnicos` (Asignación a Técnicos de
// Control Interno) — confirmado con Turco el 18/09/2026, tabla nueva.
//
// No se guardan N_RETIROS / TOTAL_VALORIZADO / PENDIENTE_DE_DESCUENTO: son
// columnas calculadas en el Excel a partir de COTIZACIONES (CM-4/CM-5, que
// todavía no existen) — se implementan como métodos del modelo cuando
// lleguemos a esos submódulos, mismo patrón kardex de Material/Herramienta.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuadrillas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            // CONTRATISTA | PERSONAL DIRECTO. Determina todo el circuito
            // aguas abajo (cotización con IGV vs. vale a costo).
            $table->string('tipo', 20);
            $table->string('estado', 20)->default('ACTIVO');
            $table->string('dni', 20)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('celular', 20)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuadrillas');
    }
};
