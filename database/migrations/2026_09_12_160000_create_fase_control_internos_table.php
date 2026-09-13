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

            // CI-3: columnas "amarillas" del Excel — nunca las toca la carga
            // del portal (ProcessExcelJob), solo el staff a mano.
            $table->date('fecha_construccion_control')->nullable()
                ->comment('F. CONSTRUCCIÓN (control) — la ingresa el staff a mano, no la carga del portal');
            // CI-5: "F. TC (portal)" del Excel — fecha que queda registrada
            // cuando la solicitud pasa a fase TC; CI-4 la completa al mover
            // de fase, CI-5 la usa para el indicador CICLO.
            $table->date('fecha_tc')->nullable()
                ->comment('F. TC (portal) — fecha de "Fecha de Registro de resultado de TC" del portal, completada por CI-4 al pasar a fase TC');
            $table->text('observacion_control')->nullable()
                ->comment('OBSERVACIÓN del Excel — notas libres del staff');
            $table->boolean('marcado_para_anular')->default(false)
                ->comment('Equivalente a la "X" de ANULAR del Excel; CI-4 la usa para mover a PEND_ANULACION');

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
