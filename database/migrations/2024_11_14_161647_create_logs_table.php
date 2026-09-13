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
            // CI-8: contadores de Control Interno de esta carga (equivalente
            // al resumen de la hoja INICIO del Excel) — nuevas_general,
            // movidas_general_construido, movidas_general_tc,
            // movidas_construido_tc, movidas_a_pend_anulacion,
            // filas_omitidas_validacion.
            $table->json('resumen_control_interno')->nullable()
                ->comment('Contadores de Control Interno de esta carga');
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
