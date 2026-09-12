<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * CI-5: "F. TC (portal)" del Excel — la fecha que queda registrada cuando
     * una solicitud pasa a fase TC (tomada de "Fecha de Registro de resultado
     * de TC" del portal, columna 99). CI-4 la completa al mover a TC; CI-5 la
     * usa para el indicador CICLO (días totales suscripción -> TC).
     */
    public function up(): void
    {
        Schema::table('fase_control_internos', function (Blueprint $table) {
            $table->date('fecha_tc')->nullable()->after('fecha_construccion_control')
                ->comment('F. TC (portal) — fecha de "Fecha de Registro de resultado de TC" del portal, completada por CI-4 al pasar a fase TC');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fase_control_internos', function (Blueprint $table) {
            $table->dropColumn('fecha_tc');
        });
    }
};
