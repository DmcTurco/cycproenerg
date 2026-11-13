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
            // Datos para los documentos de habilitación (se editan en Empresas).
            $table->string('direccion')->nullable();
            $table->string('distrito')->nullable();
            $table->string('provincia')->nullable();
            $table->string('departamento')->nullable();
            $table->string('categoria', 10)->nullable()->comment('Categoría de la empresa instaladora, ej. IG 3');
            $table->string('telefono', 30)->nullable();
            $table->string('correo')->nullable();
            $table->string('numero_poliza', 30)->nullable();
            $table->string('representante_legal')->nullable();
            // Rutas en el disco public (storage/app/public).
            $table->string('logo')->nullable();
            $table->string('sello')->nullable()->comment('Firma y sello del representante legal');
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
