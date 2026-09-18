<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * CM-1: equivalente al bloque de MATERIALES de la hoja CATALOGO
     * (filas 5-161 del Excel). Guarda solo lo que el staff edita a mano;
     * todo lo que en el Excel es kardex/fórmula (ingresos, salidas, stock
     * actual, precio vigente, alerta) se calcula al vuelo en el modelo
     * `Material`, a medida que existan las tablas que lo alimentan
     * (`ingresos` en CM-3, `ejecutados`/`cotiz_detalles` en CM-4/CM-6) —
     * mismo criterio que Control Interno con ControlInternoIndicadores: no
     * se guarda algo que se desactualizaría solo.
     */
    public function up(): void
    {
        Schema::create('materiales', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->unique()
                ->comment('CATALOGO!B — correlativo del catálogo, ej. 20251001');
            $table->string('descripcion');
            $table->string('unidad', 20)
                ->comment('CATALOGO!D — ej. RLL, UNID');
            $table->decimal('precio_base', 10, 2)
                ->comment('CATALOGO!O — editable a mano; el precio vigente sube solo desde este valor cuando INGRESOS trae un precio mayor (CM-3)');
            $table->decimal('margen_pct', 5, 4)->nullable()
                ->comment('CATALOGO!F — margen por ítem; si es null se usa parametros_control_materiales.margen_general');
            $table->decimal('stock_inicial', 12, 2)->default(0)
                ->comment('CATALOGO!I — editable a mano (carga inicial o después de un CM-11 Cierre de mes)');
            $table->decimal('stock_minimo', 12, 2)->default(0)
                ->comment('CATALOGO!M — bajo este valor el estado pasa a REPONER');
            $table->decimal('factor_metros_por_unidad', 8, 2)->default(1)
                ->comment('CATALOGO!R — tuberías: se ENTREGAN en rollos y se EJECUTAN en metros (ej. 200, 100); el resto de materiales usa 1');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materiales');
    }
};
