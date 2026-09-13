<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * CI-6 (parte segura): captura de las columnas "Rechazada", "Anulada" y
     * "Motivo de anulación" del portal (columnas 55/56/57 del Excel,
     * confirmadas con una descarga real). El VBA original elimina la fila
     * del libro cuando Rechazada o Anulada = "Sí" (ver PortalAnulada en
     * Modulo_Internas.bas), pero acá SOLO se capturan los datos — la regla de
     * qué hacer con una `Solicitud` marcada así queda deliberadamente sin
     * implementar: `Solicitud` es una tabla compartida con el módulo de
     * Asignación a Técnicos (preexistente, no forma parte de Control
     * Interno), y en una descarga real el ~73% de las filas tenía
     * Anulada = "Sí" (ver hallazgo de CI-5), así que aplicar la regla del VBA
     * tal cual borraría o archivaría la gran mayoría de las solicitudes del
     * sistema. Falta decidir con Turco qué significa "eliminar" en este
     * sistema antes de escribir esa regla.
     */
    public function up(): void
    {
        Schema::table('instalacions', function (Blueprint $table) {
            $table->boolean('rechazada')->default(false)->after('resultado_instalacion_tc')
                ->comment('Columna "Rechazada" del portal (Sí/No)');
            $table->boolean('anulada')->default(false)->after('rechazada')
                ->comment('Columna "Anulada" del portal (Sí/No) — ~73% "Sí" en una descarga real revisada el 12/09/2026, ver docs/modulos/control-interno.md CI-5/CI-6');
            $table->text('motivo_anulacion')->nullable()->after('anulada')
                ->comment('Columna "Motivo de anulación" del portal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instalacions', function (Blueprint $table) {
            $table->dropColumn(['rechazada', 'anulada', 'motivo_anulacion']);
        });
    }
};
