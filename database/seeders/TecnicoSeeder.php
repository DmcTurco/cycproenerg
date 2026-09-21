<?php

namespace Database\Seeders;

use App\Models\Tecnico;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TecnicoSeeder extends Seeder
{
    /**
     * Datos de ejemplo para desarrollo/pruebas. A diferencia de Clientes
     * (que se cargan desde el Excel real de la empresa), los técnicos no
     * tienen esa fuente, así que acá sí vale la pena un catálogo de
     * ejemplo para no arrancar con la tabla vacía tras un migrate:fresh.
     */
    public function run(): void
    {
        $tecnicos = [
            ['nombre' => 'Carlos Rodríguez Paredes', 'tipo_documento' => 1, 'numero_documento_identificacion' => 71234561, 'cargo' => 1, 'email' => 'carlos.rodriguez@cycproenerg.com'],
            ['nombre' => 'Luis Fernández Quispe', 'tipo_documento' => 1, 'numero_documento_identificacion' => 71234562, 'cargo' => 2, 'email' => 'luis.fernandez@cycproenerg.com'],
            ['nombre' => 'Jorge Huamán Torres', 'tipo_documento' => 1, 'numero_documento_identificacion' => 71234563, 'cargo' => 2, 'email' => 'jorge.huaman@cycproenerg.com'],
            ['nombre' => 'Miguel Ángel Salazar Vega', 'tipo_documento' => 1, 'numero_documento_identificacion' => 71234564, 'cargo' => 2, 'email' => 'miguel.salazar@cycproenerg.com'],
            ['nombre' => 'Pedro Ramírez Castillo', 'tipo_documento' => 1, 'numero_documento_identificacion' => 71234565, 'cargo' => 1, 'email' => 'pedro.ramirez@cycproenerg.com'],
        ];

        foreach ($tecnicos as $tecnico) {
            Tecnico::create($tecnico + [
                'empresa_id' => 1,
                'password' => Hash::make('0000'),
            ]);
        }
    }
}
