<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// CM-7: Entregas de herramientas (hoja ENTREGAS). Registro manual de
// movimientos de herramientas a las cuadrillas — NO tiene macro dedicada en
// el Excel (a diferencia de COTIZACION/REGISTRO RAPIDO): es un log simple,
// una fila por movimiento (ENTREGA o DEVOLUCION).
//
// La columna PERSONA del Excel (texto libre) se mapea a `cuadrilla_id`
// (FK a `cuadrillas`), igual convención que el resto del módulo — no texto
// libre, para poder cruzar reportes por cuadrilla más adelante.
//
// De acá sale RESPONSABLE ACTUAL y UBICACION del catálogo de herramientas
// (CATALOGO!I/J, calculados con LOOKUP(2,1/... ) = "última fila que
// coincide con este código"): Herramienta::responsableActual()/ubicacion()
// (CM-1, hoy placeholders) se resuelven contra esta tabla.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entregas', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->foreignId('herramienta_id')->constrained('herramientas')->restrictOnDelete();
            // ENTREGA | DEVOLUCION.
            $table->string('tipo', 20);
            $table->foreignId('cuadrilla_id')->constrained('cuadrillas')->restrictOnDelete();
            $table->text('observacion')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entregas');
    }
};
