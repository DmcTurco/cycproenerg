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
        Schema::create('solicitud_tecnico', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tecnico_id');
            $table->unsignedBigInteger('solicitud_id')->unique();
            $table->text('categoria');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tecnico_id')->references('id')->on('tecnicos');
            $table->foreign('solicitud_id')->references('id')->on('solicituds');
            
            $table->unique(['tecnico_id', 'solicitud_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitante_tecnico');
    }
};