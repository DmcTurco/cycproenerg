<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Control General (Internas · TC · Habilitación): traducción del Excel
     * "Control General CLB - C&C.xlsm" que usa la usuaria de esas áreas.
     * Ver docs/modulos/control-general.md.
     *
     * Una fila por solicitud = "en qué hoja vive la fila" del Excel
     * (1-Aprobadas, 2-TC Construidas, 3-Habilitación,
     * 4-Anulaciones-Reingresos, 5-Habilitadas). Reemplaza al antiguo
     * módulo Control Interno (fases GENERAL/CONSTRUIDO/TC), que se eliminó.
     *
     * Asesor, cuadrilla, técnico y contratista se guardan como TEXTO (con
     * sugerencias de las tablas del sistema en pantalla), igual que en el
     * Excel, donde se elige de una lista o se escribe un nombre nuevo.
     */
    public function up(): void
    {
        Schema::create('control_generales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('solicitud_id')->unique();
            $table->string('etapa', 20)->index()
                ->comment('APROBADA, TC_CONSTRUIDA, HABILITACION, ANULACION o HABILITADA');
            $table->date('fecha_ingreso_etapa')->nullable()
                ->comment('Desde cuándo está en la etapa actual');

            // Dato del portal, recalculado en cada carga: Fecha de suscripción
            // de contrato, o la de entrega de documentos si falta.
            $table->date('fecha_aprobacion')->nullable();

            // Lo que llena la usuaria (columnas amarillas del Excel).
            $table->boolean('construida')->default(false);
            $table->string('asesor')->nullable();
            $table->string('cuadrilla')->nullable();
            $table->string('contratista')->nullable();
            $table->text('observacion')->nullable();
            $table->date('fecha_observacion')->nullable();

            // Resultado de TC: lo trae el portal, pero ella lo puede corregir.
            // `resultado_tc_portal` es el último valor que mandó el portal:
            // si en la próxima carga el portal trae OTRO valor, gana el portal;
            // si trae el mismo, se respeta lo que ella marcó (MergeVal del VBA).
            $table->string('resultado_tc', 30)->nullable();
            $table->string('resultado_tc_portal', 30)->nullable();

            // Anulaciones y reingresos.
            $table->string('accion', 20)->nullable()->comment('Anular o Reingresar');
            $table->date('fecha_marca')->nullable();
            $table->string('estado_tramite', 20)->nullable()
                ->comment('Pendiente, En trámite, Reingresado o Anulado');

            // Habilitación.
            $table->date('fecha_programacion')->nullable();
            $table->string('tecnico')->nullable();
            $table->boolean('observado')->default(false);
            $table->boolean('observado_portal')->nullable()
                ->comment('Último valor del portal (estado 08.1/10.1), misma regla que resultado_tc_portal');
            $table->boolean('habilitado')->default(false);
            $table->date('fecha_habilitacion')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        // Parámetros del módulo (una sola fila, ParametroControlGeneral::actual()).
        Schema::create('parametros_control_generales', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('dias_limite_aprobacion')->default(20)
                ->comment('Días calendario desde la aprobación antes de marcar EN DESFASE (Resumen!C5 del Excel)');
            $table->json('ambito_departamentos')->nullable()
                ->comment('Provincias que entran al control (por defecto LIMA y CALLAO)');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parametros_control_generales');
        Schema::dropIfExists('control_generales');
    }
};
