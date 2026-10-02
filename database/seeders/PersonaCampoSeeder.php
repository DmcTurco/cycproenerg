<?php

namespace Database\Seeders;

use App\Models\PersonaCampo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PersonaCampoSeeder extends Seeder
{
    /**
     * Datos de ejemplo para desarrollo/pruebas (une los antiguos
     * TecnicoSeeder y CuadrillaSeeder). Los que tienen email usan la app
     * móvil (contraseña 0000).
     */
    public function run(): void
    {
        $personas = [
            ['nombre' => 'Carlos Rodríguez Paredes', 'tipo' => PersonaCampo::TIPO_PERSONAL_DIRECTO, 'tipo_documento' => 1, 'numero_documento' => '71234561', 'celular' => '987654301', 'email' => 'carlos.rodriguez@cycproenerg.com'],
            ['nombre' => 'Luis Fernández Quispe', 'tipo' => PersonaCampo::TIPO_PERSONAL_DIRECTO, 'tipo_documento' => 1, 'numero_documento' => '71234562', 'celular' => '987654302', 'email' => 'luis.fernandez@cycproenerg.com'],
            ['nombre' => 'Jorge Huamán Torres', 'tipo' => PersonaCampo::TIPO_PERSONAL_DIRECTO, 'tipo_documento' => 1, 'numero_documento' => '71234563', 'email' => 'jorge.huaman@cycproenerg.com'],
            ['nombre' => 'Julio César Vargas Ponce', 'tipo' => PersonaCampo::TIPO_PERSONAL_DIRECTO, 'tipo_documento' => 1, 'numero_documento' => '45781236', 'fecha_nacimiento' => '1990-04-12', 'celular' => '987654321'],
            ['nombre' => 'Rosa Elvira Núñez Campos', 'tipo' => PersonaCampo::TIPO_PERSONAL_DIRECTO, 'tipo_documento' => 1, 'numero_documento' => '46892541', 'fecha_nacimiento' => '1988-09-23', 'celular' => '987654322'],
            ['nombre' => 'Instalaciones Gas Perú S.A.C.', 'tipo' => PersonaCampo::TIPO_CONTRATISTA, 'tipo_documento' => 2, 'numero_documento' => '20601234561', 'email' => 'instalaciones.gasperu@cycproenerg.com'],
            ['nombre' => 'Redes y Tuberías del Sur E.I.R.L.', 'tipo' => PersonaCampo::TIPO_CONTRATISTA, 'tipo_documento' => 2, 'numero_documento' => '20601234562'],
            ['nombre' => 'Contratista Andino S.A.C.', 'tipo' => PersonaCampo::TIPO_CONTRATISTA, 'estado' => PersonaCampo::ESTADO_INACTIVO],
        ];

        foreach ($personas as $persona) {
            $persona = $persona + ['estado' => PersonaCampo::ESTADO_ACTIVO];

            if (isset($persona['email'])) {
                $persona['password'] = Hash::make('0000');
            }

            PersonaCampo::create($persona);
        }
    }
}
