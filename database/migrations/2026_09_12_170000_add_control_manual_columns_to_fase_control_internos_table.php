<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * CI-3: columnas "amarillas" del Excel — nunca las toca la carga del
     * portal (ProcessExcelJob), solo el staff a mano:
     *  - fecha_construccion_control: fecha de construcción que ingresa el
     *    staff (distinta de instalacions.fecha_finalizacion_instalacion_interna,
     *    que es la que trae el portal).
     *  - observacion_control: notas libres del staff.
     *  - marcado_para_anular: equivalente a la "X" de ANULAR del Excel; CI-4
     *    la usará para mover la solicitud a PEND_ANULACION.
     * Van en fase_control_internos (no en solicituds) para mantener todo lo
     * específico de Control Interno junto en una sola tabla.
     */
    public function up(): void
    {
        Schema::table('fase_control_internos', function (Blueprint $table) {
            $table->date('fecha_construccion_control')->nullable()->after('fecha_ingreso_general')
                ->comment('F. CONSTRUCCIÓN (control) — la ingresa el staff a mano, no la carga del portal');
            $table->text('observacion_control')->nullable()->after('fecha_construccion_control')
                ->comment('OBSERVACIÓN del Excel — notas libres del staff');
            $table->boolean('marcado_para_anular')->default(false)->after('observacion_control')
                ->comment('Equivalente a la "X" de ANULAR del Excel; CI-4 la usa para mover a PEND_ANULACION');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fase_control_internos', function (Blueprint $table) {
            $table->dropColumn(['fecha_construccion_control', 'observacion_control', 'marcado_para_anular']);
        });
    }
};
