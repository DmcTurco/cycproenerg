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
        Schema::create('proyectos', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_proyecto')->nullable();
            $table->string('codigo_proyecto')->nullable();
            $table->string('categoria_proyecto')->nullable();
            $table->string('sub_categoria_proyecto')->nullable();
            $table->string('codigo_objeto_conexion')->nullable();
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
        Schema::dropIfExists('proyectos');
    }
};
