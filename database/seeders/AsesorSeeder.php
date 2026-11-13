<?php

namespace Database\Seeders;

use App\Models\Asesor;
use Illuminate\Database\Seeder;

class AsesorSeeder extends Seeder
{
    /**
     * Datos de ejemplo para desarrollo/pruebas. Son los que salen en la lista
     * de Asesor de Control General. Se puede correr varias veces: busca por
     * número de documento y no duplica.
     */
    public function run(): void
    {
        $asesores = [
            ['nombre' => 'Ana María Pérez Ramos', 'numero_documento_identificacion' => 41234561, 'telefono' => '987111201', 'email' => 'ana.perez@cycproenerg.com', 'fecha_contratacion' => '2024-03-04', 'comision' => 3.50],
            ['nombre' => 'Marco Antonio Salazar Vega', 'numero_documento_identificacion' => 41234562, 'telefono' => '987111202', 'email' => 'marco.salazar@cycproenerg.com', 'fecha_contratacion' => '2024-05-20', 'comision' => 3.00],
            ['nombre' => 'Lucía Fernanda Medina Cruz', 'numero_documento_identificacion' => 41234563, 'telefono' => '987111203', 'email' => 'lucia.medina@cycproenerg.com', 'fecha_contratacion' => '2025-01-13', 'comision' => 3.50],
            ['nombre' => 'Diego Alonso Ríos Castillo', 'numero_documento_identificacion' => 41234564, 'telefono' => '987111204', 'fecha_contratacion' => '2025-06-02', 'comision' => 2.50],
            ['nombre' => 'Patricia Elena Soto Aguilar', 'numero_documento_identificacion' => 41234565, 'telefono' => '987111205', 'fecha_contratacion' => '2023-11-15', 'comision' => 3.00, 'estado' => 'inactivo'],
        ];

        foreach ($asesores as $asesor) {
            Asesor::updateOrCreate(
                ['numero_documento_identificacion' => $asesor['numero_documento_identificacion']],
                $asesor + [
                    'company_id' => 1,
                    'tipo_documento' => 'DNI',
                    'estado' => 'activo',
                    'total_ventas' => 0,
                    'numero_ventas' => 0,
                ],
            );
        }
    }
}
