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
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_documento')->nullable();
            $table->string('numero_documento')->nullable();
            // Código corto de Control Interno (CYC/CLB), igual que PARAM!B6:B7
            // del Excel "CONTROL INTERNAS - CYC CLB v5.4.xlsm". Se completa
            // solo por RUC, en cada carga de Excel, desde
            // ProcessExcelJob::asignarCodigoEmpresa() — ver config/const.php
            // (control_interno.codigos_empresa_por_ruc). Antes vivía en una
            // migración aparte (add_codigo_to_empresas_table); se juntó acá
            // el 13/09/2026 porque el proyecto todavía está en desarrollo y
            // no tiene sentido mantener columnas de días distintos separadas.
            $table->string('codigo', 10)->nullable()->unique();
            $table->string('nombre')->nullable();
            $table->string('registro_gas_natural')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};
