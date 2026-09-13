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

            // CI-5 (13/09/2026): caché de ControlInternoIndicadores::para().
            // Antes todo se calculaba 100% al vuelo (nunca se guardaba, para
            // no desactualizarse); se decidió cachear estos 4 campos porque
            // Resumen (CI-10) y Puntaje (CI-7) necesitaban recorrer TODAS las
            // solicitudes de una fase en cada request en PHP (para sumar
            // semáforos uno por uno) en vez de poder usar COUNT/GROUP BY en
            // SQL directamente sobre esta tabla. Se recalculan y se guardan
            // solos cada vez que la solicitud cambia (ProcessExcelJob,
            // ControlInternoManualController::calcularYGuardar) y, además,
            // una vez al día vía el comando
            // `control-interno:recalcular-indicadores` (routes/console.php),
            // porque DESFACE/SEMÁFORO de GENERAL y CONSTRUIDO comparan contra
            // "hoy" y se desactualizan solos aunque nada cambie en la
            // solicitud. La vista de detalle (CI-3) nunca lee estos campos
            // guardados: siempre pide el cálculo en vivo (para()), que de
            // paso los refresca.
            $table->string('ind_semaforo', 20)->nullable()
                ->comment('Caché de para()["semaforo"] — ver comentario arriba');
            $table->integer('ind_desface_dias')->nullable()
                ->comment('Caché de para()["desface_dias"]');
            $table->integer('ind_dias_habiles')->nullable()
                ->comment('Caché de para()["dias_habiles"]');
            $table->boolean('ind_fuera_de_plazo')->nullable()
                ->comment('Caché de para()["fuera_de_plazo"]');
            $table->timestamp('ind_actualizado_en')->nullable()
                ->comment('Cuándo se calculó por última vez este caché (calcularYGuardar)');

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
