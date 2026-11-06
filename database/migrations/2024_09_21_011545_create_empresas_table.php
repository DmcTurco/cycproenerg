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
            // Código corto de empresa (CYC/CLB). Se completa solo por RUC, en
            // cada carga de Excel, desde ProcessExcelJob::asignarCodigoEmpresa()
            // — ver config/const.php (empresas.codigos_por_ruc).
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
