<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('instalacions', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_instalacion')->nullable();
            $table->string('tipo_acometida')->nullable();
            $table->integer('numero_puntos_instalacion')->nullable();
            $table->date('fecha_finalizacion_instalacion_interna')->nullable();
            $table->date('fecha_finalizacion_instalacion_acometida')->nullable();
            $table->string('resultado_instalacion_tc')->nullable();

            // CI-6 (parte segura): captura de "Rechazada"/"Anulada"/"Motivo de
            // anulación" del portal. El VBA original elimina la fila cuando
            // Rechazada o Anulada = "Sí", pero acá SOLO se capturan los datos
            // — en una descarga real el ~73% de las filas tenía Anulada =
            // "Sí", así que aplicar la regla del VBA tal cual borraría o
            // archivaría la gran mayoría de las solicitudes del sistema.
            // Falta decidir con Turco qué significa "eliminar" en este
            // sistema antes de escribir esa regla (ver docs/modulos/control-interno.md, CI-6).
            $table->boolean('rechazada')->default(false)
                ->comment('Columna "Rechazada" del portal (Sí/No)');
            $table->boolean('anulada')->default(false)
                ->comment('Columna "Anulada" del portal (Sí/No) — ~73% "Sí" en una descarga real revisada el 12/09/2026');
            $table->text('motivo_anulacion')->nullable()
                ->comment('Columna "Motivo de anulación" del portal');

            $table->date('fecha_programacion_habilitacion')->nullable();
            $table->unsignedBigInteger('solicitud_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instalacions');
    }
};
