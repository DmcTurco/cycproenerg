<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// CM-10: Inventario físico de fin de mes (hoja "INVENTARIO FISICO"). En el
// Excel original es una hoja de trabajo que se llena a mano, se imprime
// para firmar, y se BORRA por completo al cerrar el mes (CM-11) — no queda
// ningún historial salvo la copia completa del archivo. Turco pidió que
// acá SÍ quede historial: cada conteo físico que se guarda es una fila
// propia en `inventarios_fisicos`, con su detalle en
// `inventario_fisico_detalles` (ver esa migración) — se puede consultar
// cualquier inventario pasado, no solo el más reciente.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventarios_fisicos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            // "REALIZADO POR" del encabezado del Excel (B3/E3) — texto
            // libre, no necesariamente el empleado logueado que lo digita.
            $table->string('realizado_por');
            // Pie de firma del Excel (B268/B270): dos campos de texto, no
            // una firma real — el papel impreso sigue siendo el que se
            // firma a mano; esto es solo para que quede quién validó.
            $table->string('nombre_almacenero')->nullable();
            $table->string('nombre_supervisor')->nullable();
            $table->text('observacion_general')->nullable();
            // Empleado que digitó el conteo en el sistema (Auth::id(), guard
            // 'employee') — mismo criterio que Logs/historials, sin FK
            // formal.
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventarios_fisicos');
    }
};
