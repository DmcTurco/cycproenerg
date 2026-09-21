<?php

namespace Database\Seeders;

use App\Models\Cuadrilla;
use Illuminate\Database\Seeder;

class CuadrillaSeeder extends Seeder
{
    /**
     * Catálogo de ejemplo para desarrollo/pruebas (CM-2). PERSONAL DIRECTO
     * son personas (dni/fecha_nacimiento/celular propios); CONTRATISTA son
     * cuadrillas externas, sin esos datos personales.
     */
    public function run(): void
    {
        $cuadrillas = [
            ['nombre' => 'Julio César Vargas Ponce', 'tipo' => Cuadrilla::TIPO_PERSONAL_DIRECTO, 'estado' => 'ACTIVO', 'dni' => '45781236', 'fecha_nacimiento' => '1990-04-12', 'celular' => '987654321'],
            ['nombre' => 'Rosa Elvira Núñez Campos', 'tipo' => Cuadrilla::TIPO_PERSONAL_DIRECTO, 'estado' => 'ACTIVO', 'dni' => '46892541', 'fecha_nacimiento' => '1988-09-23', 'celular' => '987654322'],
            ['nombre' => 'Instalaciones Gas Perú S.A.C.', 'tipo' => Cuadrilla::TIPO_CONTRATISTA, 'estado' => 'ACTIVO'],
            ['nombre' => 'Redes y Tuberías del Sur E.I.R.L.', 'tipo' => Cuadrilla::TIPO_CONTRATISTA, 'estado' => 'ACTIVO'],
            ['nombre' => 'Contratista Andino S.A.C.', 'tipo' => Cuadrilla::TIPO_CONTRATISTA, 'estado' => 'INACTIVO'],
        ];

        foreach ($cuadrillas as $cuadrilla) {
            Cuadrilla::create($cuadrilla);
        }
    }
}
