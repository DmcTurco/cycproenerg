<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Documentos que se suben a mano para una solicitud de Control General
     * (habilitación): el archivo queda en el disco privado y aquí su registro.
     */
    public function up(): void
    {
        Schema::create('control_general_archivos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('control_general_id')->constrained('control_generales')->cascadeOnDelete();
            $table->string('nombre', 150)->comment('Nombre que se muestra en la lista');
            $table->string('nombre_original', 255);
            $table->string('ruta', 255);
            $table->string('mime', 120)->nullable();
            $table->unsignedBigInteger('tamano')->default(0);
            $table->unsignedBigInteger('subido_por')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_general_archivos');
    }
};
