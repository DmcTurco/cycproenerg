<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Fase de Control Interno de cada solicitud: equivalente a "en qué hoja
     * vive la fila" en el Excel (GENERAL / CONSTRUIDO / TC / PEND_ANULACION).
     * Es 1 a 1 con solicituds y a propósito es una tabla aparte, NO la misma
     * que estado_internos: esa otra es el estado de asignación a técnico de
     * campo (pendiente/asignado/Iniciado/...), un eje totalmente distinto
     * de la misma solicitud.
     */
    public function up(): void
    {
        Schema::create('fase_control_internos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('solicitud_id')->unique();
            $table->string('fase', 20)->default('GENERAL')
                ->comment('GENERAL, CONSTRUIDO, TC o PEND_ANULACION');
            $table->date('fecha_ingreso_general')->nullable()
                ->comment('Fecha en que la solicitud entró por primera vez al control (Z. F. INGRESO del Excel); no cambia al moverse de fase');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fase_control_internos');
    }
};
