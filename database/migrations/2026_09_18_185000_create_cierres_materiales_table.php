<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// CM-11: Cierre de mes — equivalente a la macro CerrarMes de
// MacrosCYC.bas. El Excel original, al cerrar: (1) guarda una COPIA
// completa del archivo como histórico, (2) pasa STOCK ACTUAL -> STOCK
// INICIAL, (3) consolida el PRECIO VIGENTE como PRECIO BASE (solo si sube),
// (4) BORRA por completo Ingresos/Ejecutado/vales de personal
// directo/cotizaciones de contratista ya descontadas (solo sobreviven las
// PENDIENTES), (5) conserva catálogo, cuadrillas y herramientas.
//
// Turco pidió (22/09/2026) que el sistema NO borre esos datos: en vez de
// la "copia completa del archivo", cada cierre guarda su propio snapshot
// (json, columna `resumen`) con los indicadores de ese momento — y los
// movimientos del periodo que cierra se ARCHIVAN (soft-delete + FK
// `cierre_materiales_id` en ingresos/ejecutados/cotizaciones — por eso esta
// migración corre antes que esas tablas) en vez de borrarse
// de verdad, para poder seguir consultándolos. El kardex/saldos del mes
// nuevo arrancan limpios igual, porque todos los accessors del módulo
// (Material::salidas(), Cuadrilla::saldosEnPoder(), etc.) ya excluyen
// automáticamente las filas eliminadas (soft delete estándar de
// Eloquent) — no hizo falta tocar ninguno de esos métodos.
//
// A diferencia del Excel, acá NO se tocan las cotizaciones PENDIENTES
// (compensación de la macro para que el kardex no descontara doble): como
// no se les borra ni se les limpia nada, no hace falta compensar nada.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cierres_materiales', function (Blueprint $table) {
            $table->id();
            // Etiqueta del mes que cierra (ej. "2026-09"), como el
            // InputBox de la macro. Única: no se puede cerrar el mismo mes
            // dos veces.
            $table->string('etiqueta')->unique();
            // Fecha de corte: los movimientos con fecha <= esta se
            // archivan. Normalmente hoy, pero se deja editable por si se
            // cierra con unos días de atraso.
            $table->date('fecha_cierre');
            $table->unsignedBigInteger('employee_id')->nullable();
            // Snapshot completo (indicadores generales + registro de
            // cuadrillas + catálogo de materiales/herramientas) tomado
            // JUSTO ANTES de archivar nada, para no perder el detalle del
            // mes que cierra aunque después se archiven sus movimientos.
            $table->json('resumen');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cierres_materiales');
    }
};
