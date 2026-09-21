<?php

namespace Database\Seeders;

use App\Models\Material;
use Illuminate\Database\Seeder;

class MaterialSeeder extends Seeder
{
    /**
     * Catálogo de ejemplo para desarrollo/pruebas (CM-1). Incluye materiales
     * en distintos estados de stock (OK, REPONER, SIN STOCK) para poder
     * probar los indicadores del catálogo y del panel de Resumen sin tener
     * que cargar Ingresos/Ejecutados primero.
     */
    public function run(): void
    {
        $materiales = [
            ['codigo' => 'TUB-PE20', 'descripcion' => 'Tubería PE 20mm (rollo)', 'unidad' => 'RLL', 'precio_base' => 85.00, 'stock_inicial' => 40, 'stock_minimo' => 10, 'factor_metros_por_unidad' => 200],
            ['codigo' => 'TUB-PE32', 'descripcion' => 'Tubería PE 32mm (rollo)', 'unidad' => 'RLL', 'precio_base' => 145.00, 'stock_inicial' => 20, 'stock_minimo' => 5, 'factor_metros_por_unidad' => 100],
            ['codigo' => 'LLA-PASO', 'descripcion' => 'Llave de paso 1/2"', 'unidad' => 'UNID', 'precio_base' => 18.50, 'stock_inicial' => 60, 'stock_minimo' => 15, 'factor_metros_por_unidad' => 1],
            ['codigo' => 'MED-G4', 'descripcion' => 'Medidor de gas G4', 'unidad' => 'UNID', 'precio_base' => 210.00, 'stock_inicial' => 12, 'stock_minimo' => 12, 'factor_metros_por_unidad' => 1],
            ['codigo' => 'ACC-UNION', 'descripcion' => 'Unión universal PE 20mm', 'unidad' => 'UNID', 'precio_base' => 6.20, 'stock_inicial' => 0, 'stock_minimo' => 20, 'factor_metros_por_unidad' => 1],
            ['codigo' => 'REG-PRES', 'descripcion' => 'Regulador de presión doméstico', 'unidad' => 'UNID', 'precio_base' => 95.00, 'stock_inicial' => 25, 'stock_minimo' => 8, 'factor_metros_por_unidad' => 1],
        ];

        foreach ($materiales as $material) {
            Material::create($material + [
                'margen_pct' => null,
                'serie' => Material::SERIE,
                'correlativo' => Material::siguienteCorrelativo(),
            ]);
        }
    }
}
