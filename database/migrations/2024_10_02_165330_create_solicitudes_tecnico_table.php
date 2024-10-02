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
        Schema::create('solicitudes_tecnico', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tecnico_id');
            $table->unsignedBigInteger('numero_solicitud');
            $table->unsignedBigInteger('numero_documento');
            $table->unsignedBigInteger('tipo_cliente');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitudes_tecnico');
    }
};
