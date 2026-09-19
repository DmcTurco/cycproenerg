<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// CM-6: Registro rápido + Ejecutado (hojas REGISTRO RAPIDO + EJECUTADO).
// Exclusiva de PERSONAL DIRECTO: los contratistas descuentan su material
// al emitir la cotización (CM-4), nunca pasan por acá.
//
// A diferencia de cotizacion_detalles, acá NO se guarda precio/total: en
// el Excel, J (PRECIO COSTO) y K (TOTAL) de EJECUTADO son fórmulas que se
// recalculan siempre con el precio ACTUAL del catálogo (no una foto del
// momento, a diferencia de una cotización ya emitida) — se calculan en el
// modelo (Ejecutado::precioCosto()/total()), mismo criterio de kardex que
// el resto del módulo.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ejecutados', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            // Obligatorio solo si movimiento = SALIDA (igual que la macro
            // GuardarEjecutado); en una DEVOLUCION puede ir vacío.
            $table->string('tipo_trabajo', 60)->nullable();
            $table->unsignedBigInteger('cuadrilla_id');
            $table->unsignedBigInteger('material_id');
            // En la unidad que se REPORTA (metros para tuberías, la unidad
            // del catálogo para el resto) — no en la unidad de stock.
            $table->decimal('cantidad', 12, 2);
            $table->string('n_suministro', 40)->nullable();
            $table->text('observacion')->nullable();
            // SALIDA (consumido en la obra) | DEVOLUCION (fin de mes,
            // vuelve al almacén). Ambos reducen "lo que tiene en su poder"
            // por distintas razones — ver Cuadrilla::saldosEnPoder().
            $table->string('movimiento', 20);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ejecutados');
    }
};
