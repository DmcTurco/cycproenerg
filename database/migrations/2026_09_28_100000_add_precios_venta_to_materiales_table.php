<?php

use App\Models\Material;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * CATALOGO!G/H — precio de venta (sin y con IGV) guardado en la tabla,
     * a pedido de Turco (28/09/2026). A diferencia del resto de fórmulas del
     * catálogo, este SÍ se persiste; para que no quede desactualizado se
     * recalcula solo (ver Material::recalcularPreciosVenta()) cuando cambia
     * el material, un ingreso con precio o los parámetros (IGV / margen
     * general).
     */
    public function up(): void
    {
        Schema::table('materiales', function (Blueprint $table) {
            $table->decimal('precio_venta_sin_igv', 10, 2)->default(0)->after('margen_pct')
                ->comment('CATALOGO!G — precio vigente × (1 + margen efectivo); se recalcula solo');
            $table->decimal('precio_venta_con_igv', 10, 2)->default(0)->after('precio_venta_sin_igv')
                ->comment('CATALOGO!H — precio_venta_sin_igv × (1 + IGV); se recalcula solo');
        });

        // Llenar los materiales que ya existen.
        Material::withTrashed()->each(fn (Material $material) => $material->recalcularPreciosVenta());
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('materiales', function (Blueprint $table) {
            $table->dropColumn(['precio_venta_sin_igv', 'precio_venta_con_igv']);
        });
    }
};
