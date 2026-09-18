<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Pivote cuadrilla<->empresa (muchos a muchos).
//
// En el Excel real, la columna "CUADRILLA / EMPRESA" (texto libre) tiene
// casos como "CLB", "C&C" y también "CLB/C&C" en la misma celda — una
// cuadrilla puede trabajar para ambas empresas. Turco confirmó el
// 18/09/2026 que se modela como muchos a muchos (no un empresa_id único)
// para no perder ese matiz, ver docs/modulos/control-materiales.md sección 5.
//
// Nombre de tabla por convención de Laravel (alfabético de los dos
// modelos): cuadrilla_empresa.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuadrilla_empresa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuadrilla_id')->constrained()->cascadeOnDelete();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->unique(['cuadrilla_id', 'empresa_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuadrilla_empresa');
    }
};
