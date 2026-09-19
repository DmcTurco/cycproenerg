<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Líneas de una cotización/vale (equivalente a la hoja oculta
// COTIZ_DETALLE). Se reemplazan enteras cada vez que se "corrige" un
// documento (mismo criterio que la macro CorregirCotizacion/GenerarPDF:
// borra el detalle anterior de ese número y vuelve a insertar).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizacion_detalles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cotizacion_id');
            $table->unsignedBigInteger('material_id');
            $table->decimal('cantidad', 12, 2);
            // Precio unitario S/IGV al momento de emitir (vale: precio
            // vigente; cotización: precio de venta S/IGV) — se guarda como
            // foto, no se recalcula si el catálogo cambia después.
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('total', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizacion_detalles');
    }
};
