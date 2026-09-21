<?php

namespace Database\Seeders;

use App\Models\Herramienta;
use Illuminate\Database\Seeder;

class HerramientaSeeder extends Seeder
{
    /**
     * Catálogo de ejemplo para desarrollo/pruebas (CM-1). Mezcla estados
     * (OPERATIVA/MALOGRADA/PERDIDA) para poder probar los badges e
     * indicadores del panel de Resumen. `codigo` es libre (como lo
     * escribiría el staff); `correlativo` se genera igual que en la app
     * real (Herramienta::siguienteCorrelativo()), nunca a mano.
     */
    public function run(): void
    {
        $herramientas = [
            ['codigo' => 'TERMO-63', 'descripcion' => 'Termofusora 20-63mm', 'marca_modelo' => 'Ritmo Basic 63', 'numero_serie' => 'TF-0001', 'fecha_compra' => '2024-03-10', 'precio' => 3200.00, 'estado' => 'OPERATIVA'],
            ['codigo' => 'DETEC-FUGA', 'descripcion' => 'Detector de fugas de gas', 'marca_modelo' => 'BM25', 'numero_serie' => 'DF-0002', 'fecha_compra' => '2024-06-15', 'precio' => 850.00, 'estado' => 'OPERATIVA'],
            ['codigo' => 'TALADRO-01', 'descripcion' => 'Taladro percutor', 'marca_modelo' => 'Bosch GSB 550', 'numero_serie' => 'TP-0003', 'fecha_compra' => '2023-11-02', 'precio' => 320.00, 'estado' => 'OPERATIVA'],
            ['codigo' => 'STILLSON-18', 'descripcion' => 'Llave stillson 18"', 'marca_modelo' => 'Stanley', 'numero_serie' => 'LS-0004', 'fecha_compra' => '2022-08-20', 'precio' => 95.00, 'estado' => 'MALOGRADA', 'observacion' => 'Mordaza gastada, pendiente de reparar.'],
            ['codigo' => 'MANOM-DIG', 'descripcion' => 'Manómetro digital', 'marca_modelo' => 'Testo 550', 'numero_serie' => 'MD-0005', 'fecha_compra' => '2023-01-05', 'precio' => 680.00, 'estado' => 'PERDIDA', 'observacion' => 'No fue devuelto tras la instalación del 2025-05-12.'],
        ];

        foreach ($herramientas as $herramienta) {
            Herramienta::create($herramienta + [
                'serie' => Herramienta::SERIE,
                'correlativo' => Herramienta::siguienteCorrelativo(),
            ]);
        }
    }
}
