<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Equivalente al rango de feriados de PARAM!J17:J40, usado para el
     * cálculo de días hábiles (NETWORKDAYS en el Excel). A diferencia del
     * Excel, aquí no hay límite de filas: se agregan feriados sin tocar
     * ninguna fórmula.
     */
    public function up(): void
    {
        Schema::create('feriados', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->unique();
            $table->string('descripcion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feriados');
    }
};
