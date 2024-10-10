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
        Schema::create('vendedores', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('company_id');
            $table->text('nombre');
            $table->text('tipo_documento')->nullable();
            $table->unsignedInteger('numero_documento_identificacion')->nullable();
            $table->text('telefono')->nullable();
            $table->text('email')->nullable();
            $table->text('direccion')->nullable();
            $table->date('fecha_contratacion')->nullable();
            $table->decimal('comision', 5, 2)->nullable();
            $table->decimal('total_ventas', 10, 2)->nullable();
            $table->unsignedInteger('numero_ventas')->nullable();
            $table->text('estado')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendedores');
    }
};
