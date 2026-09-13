<?php

namespace App\Console\Commands;

use App\Models\Solicitud;
use App\Services\ControlInternoIndicadores;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * CI-5 (13/09/2026): refresco diario del caché de indicadores
 * (fase_control_internos.ind_*, ver ControlInternoIndicadores). Necesario
 * porque ProcessExcelJob y ControlInternoManualController solo recalculan la
 * solicitud que tocan; DESFACE/SEMÁFORO de las fases GENERAL y CONSTRUIDO
 * comparan contra "hoy" (Carbon::today()), así que se desactualizan solos
 * con el simple paso del tiempo aunque nadie vuelva a tocar esa solicitud.
 *
 * Programado en routes/console.php (Schedule::command(...)->dailyAt(...)),
 * igual que el resto de tareas de este proyecto (ver queue:process,
 * queue:prune-failed, etc. en ese mismo archivo) — Laravel 12 ya no usa
 * app/Console/Kernel.php para esto.
 */
class RecalcularIndicadoresControlInterno extends Command
{
    protected $signature = 'control-interno:recalcular-indicadores';
    protected $description = 'Recalcula y guarda el caché de indicadores de Control Interno (CI-5) de todas las solicitudes';

    public function handle(): int
    {
        $total = 0;

        // chunkById en vez de get()/each(): con miles de solicitudes, traer
        // todo de una sola vez a memoria es justo el problema de performance
        // que este mismo módulo ya tuvo (ver docs/modulos/control-interno.md,
        // sección 5, punto 10).
        Solicitud::with(['faseControlInterno', 'instalacion'])
            ->chunkById(200, function ($solicitudes) use (&$total) {
                foreach ($solicitudes as $solicitud) {
                    ControlInternoIndicadores::calcularYGuardar($solicitud);
                    $total++;
                }
            });

        // CI-10: el Resumen cachea 5 minutos; sin este forget, un refresco
        // recién hecho podría no verse hasta que venza ese caché por su
        // cuenta.
        Cache::forget('control_interno_dashboard_' . now()->year . '_' . (int) ceil(now()->month / 3));

        $this->info("Indicadores de Control Interno recalculados: {$total} solicitudes.");

        return self::SUCCESS;
    }
}
