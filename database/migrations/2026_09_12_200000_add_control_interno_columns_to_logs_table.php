<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * CI-8: bitácora de cargas ampliada — equivalente al bloque de resumen de
     * la hoja INICIO del Excel. La tabla `logs` ya existía (nombre_archivo,
     * total_filas, filas_procesadas, filas_con_error, errores, estado,
     * employee_id) pero nada la poblaba todavía: ni `ClientController::change()`
     * ni `ProcessExcelJob` escribían ahí (se confirmó revisando ambos al
     * construir CI-8) — el progreso solo vivía en caché (`excel_progress_*`),
     * que es efímera y se usa solo para la barra de progreso, no como
     * bitácora histórica. Esta migración agrega el campo para los contadores
     * específicos de Control Interno; el de escribir en `logs` en general se
     * resuelve en el mismo cambio (ClientController + ProcessExcelJob).
     */
    public function up(): void
    {
        Schema::table('logs', function (Blueprint $table) {
            $table->json('resumen_control_interno')->nullable()->after('errores')
                ->comment('Contadores de Control Interno de esta carga: nuevas_general, movidas_general_construido, movidas_general_tc, movidas_construido_tc, movidas_a_pend_anulacion, filas_omitidas_validacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logs', function (Blueprint $table) {
            $table->dropColumn('resumen_control_interno');
        });
    }
};
