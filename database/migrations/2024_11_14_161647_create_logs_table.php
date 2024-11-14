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
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_archivo');
            $table->integer('total_filas');
            $table->float('tamaño_archivo')->comment('Tamaño en MB');
            $table->integer('filas_procesadas')->default(0);
            $table->integer('filas_con_error')->default(0);
            $table->text('errores')->nullable();
            $table->enum('estado', ['en_proceso', 'completado', 'error'])->default('en_proceso');
            $table->unsignedBigInteger('employee_id');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};
