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
            $table->string('numero_solicitud')->nullable();
            $table->string('numero_suministro')->nullable();
            $table->string('numero_contrato_suministro')->nullable();
            $table->date('fecha_aprobacion_contrato')->nullable();
            $table->date('fecha_registro_portal')->nullable();
            $table->string('estado_solicitud')->nullable();
            $table->unsignedBigInteger('solicitante_id')->nullable();
            $table->unsignedBigInteger('empresa_id')->nullable();
            $table->unsignedBigInteger('concesionaria_id')->nullable();
            $table->unsignedBigInteger('asesor_id')->nullable();
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
