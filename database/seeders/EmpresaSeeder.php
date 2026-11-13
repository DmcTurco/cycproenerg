<?php

namespace Database\Seeders;

use App\Models\Empresa;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class EmpresaSeeder extends Seeder
{
    /**
     * Datos de C&C y CLB que salen en los documentos de habilitación (tomados
     * de las plantillas de Word y de los libros de actas), más su logo y la
     * firma y sello del representante legal (database/seeders/data/empresas).
     * Se pueden corregir después en Empresas. La carga del Excel del portal
     * reconoce estas filas por el RUC, así que no se duplican.
     */
    public function run(): void
    {
        $empresas = [
            'cyc' => [
                'codigo' => 'CYC',
                'ruc' => '20604329397',
                'datos' => [
                    'nombre' => 'C&C PROYECTOS INTEGRALES EN ENERGÍA S.A.C.',
                    'registro_gas_natural' => '5051',
                    'categoria' => 'IG 3',
                    'direccion' => 'Av. Prolongación Chillón Mz. "A" Lt. 11 Urb. Bello Horizonte',
                    'distrito' => 'COMAS',
                    'provincia' => 'LIMA',
                    'departamento' => 'LIMA',
                    'telefono' => '01 4898454',
                    'correo' => 'info@cycproenerg.com',
                    'numero_poliza' => '16559965',
                    'representante_legal' => 'Cristian Carlos Luquillas Bailon',
                ],
            ],
            'clb' => [
                'codigo' => 'CLB',
                'ruc' => '20610320032',
                'datos' => [
                    'nombre' => 'CLB INGENIERIA Y PROYECTOS EN ENERGIA SOSTENIBLE S.A.C.',
                    'registro_gas_natural' => '7265',
                    'categoria' => 'IG 3',
                    'direccion' => 'Av. Prolongación Chillón Mz. "A" Lt. 11 Piso 02 Urb. Bello Horizonte',
                    'distrito' => 'COMAS',
                    'provincia' => 'LIMA',
                    'departamento' => 'LIMA',
                    'telefono' => '999 055 486',
                    'correo' => 'Clb.ingenieria93@gmail.com',
                    'numero_poliza' => '15153416',
                    'representante_legal' => 'Cristian Carlos Luquillas Bailon',
                ],
            ],
        ];

        foreach ($empresas as $clave => $e) {
            $empresa = Empresa::withTrashed()->firstOrNew(['numero_documento' => $e['ruc']]);
            if ($empresa->trashed()) {
                $empresa->restore();
            }
            $empresa->fill(['tipo_documento' => '2', 'codigo' => $e['codigo']] + $e['datos'])->save();

            foreach (['logo', 'sello'] as $imagen) {
                $origen = database_path("seeders/data/empresas/{$clave}-{$imagen}.png");
                if (!is_file($origen)) {
                    continue;
                }
                $destino = "empresas/{$clave}-{$imagen}.png";
                Storage::disk('public')->put($destino, file_get_contents($origen));
                $empresa->update([$imagen => $destino]);
            }
        }
    }
}
