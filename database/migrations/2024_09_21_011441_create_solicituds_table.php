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
        Schema::create('solicituds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('solicitante_id');
            $table->unsignedBigInteger('ubicacion_id')->nullable();
            $table->unsignedBigInteger('proyecto_id')->nullable();
            $table->string('numero_solicitud')->nullable();
            $table->string('codigo_identificacion_predio')->nullable();
            $table->string('numero_suministro')->nullable();
            $table->string('numero_contrato_suministro')->nullable();
            $table->timestamp('fecha_registro_aprobacion_portal')->nullable();
            $table->timestamp('fecha_aprobacion_contrato')->nullable();
            $table->timestamp('fecha_registro_solicitud_portal')->nullable();
            $table->timestamp('fecha_programada_instalacion_interna')->nullable();
            $table->timestamp('fecha_inicio_instalacion_interna')->nullable();
            $table->timestamp('fecha_finalizacion_instalacion_interna')->nullable();
            $table->timestamp('fecha_finalizacion_instalacion_acometida')->nullable();
            $table->timestamp('fecha_programacion_habilitacion')->nullable();
            $table->timestamp('fecha_entrega_documentos_concesionario')->nullable();
            $table->string('estado_solicitud')->nullable();
            $table->string('ultima_accion_realizada')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicituds');
    }
};
