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
