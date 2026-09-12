<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\Feriado;
use App\Models\ParametroControlInterno;
use Illuminate\Database\Seeder;

class ControlInternoParametrosSeeder extends Seeder
{
    /**
     * Carga los parámetros y feriados con los mismos valores que traía la
     * hoja PARAM del Excel "CONTROL INTERNAS - CYC CLB v5.4.xlsm", y asigna
     * el código CYC/CLB a las empresas que ya existan con esos RUC.
     */
    public function run(): void
    {
        ParametroControlInterno::updateOrCreate(['id' => 1], [
            'plazo_construccion_dias_habiles' => 20,
            'semaforo_verde_dias' => 10,
            'semaforo_ambar_dias' => 20,
            'espera_tc_verde_dias' => 15,
            'espera_tc_ambar_dias' => 30,
            'meta_ind2' => 0.95,
            'ambito_departamentos' => ['LIMA', 'CALLAO'],
        ]);

        // RUC -> código corto (PARAM!B6:E7). No crea la Empresa si todavía
        // no existe (eso lo hace la carga del portal); solo completa el
        // código cuando la fila ya está en la base de datos.
        $codigosPorRuc = [
            '20604329397' => 'CYC', // C&C PROYECTOS INTEGRALES EN ENERGÍA S.A.C.
            '20610320032' => 'CLB', // CLB INGENIERIA Y PROYECTOS EN ENERGIA SOSTENIBLE S.A.C.
        ];
        foreach ($codigosPorRuc as $ruc => $codigo) {
            Empresa::where('numero_documento', $ruc)->update(['codigo' => $codigo]);
        }

        // Feriados 2026 (PARAM!J17:J40 del Excel de origen).
        $feriados = [
            ['2026-01-01', 'Año Nuevo'],
            ['2026-04-02', 'Jueves Santo'],
            ['2026-04-03', 'Viernes Santo'],
            ['2026-05-01', 'Día del Trabajo'],
            ['2026-06-07', 'Batalla de Arica y Alfonso Ugarte'],
            ['2026-06-29', 'San Pedro y San Pablo'],
            ['2026-07-23', null],
            ['2026-07-28', 'Fiestas Patrias'],
            ['2026-07-29', 'Fiestas Patrias'],
            ['2026-08-06', 'Batalla de Junín'],
            ['2026-08-30', 'Santa Rosa de Lima'],
            ['2026-10-08', 'Combate de Angamos'],
            ['2026-11-01', 'Todos los Santos'],
            ['2026-12-08', 'Inmaculada Concepción'],
            ['2026-12-09', 'Batalla de Ayacucho'],
            ['2026-12-25', 'Navidad'],
        ];
        foreach ($feriados as [$fecha, $descripcion]) {
            Feriado::updateOrCreate(['fecha' => $fecha], ['descripcion' => $descripcion]);
        }
    }
}
