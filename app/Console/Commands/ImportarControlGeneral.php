<?php

namespace App\Console\Commands;

use App\Models\ControlGeneral;
use App\Services\ControlGeneralImportador;
use Illuminate\Console\Command;

/**
 * Importa el trabajo manual del Excel "Control General CLB - C&C.xlsm" al
 * módulo Control General. Por defecto solo muestra lo que haría; con
 * --confirmar guarda. Se puede volver a correr: actualiza, no duplica.
 *
 *   php artisan control-general:importar-excel "C:\ruta\Control General CLB - C&C.xlsm"
 *   php artisan control-general:importar-excel "C:\ruta\Control General CLB - C&C.xlsm" --confirmar
 */
class ImportarControlGeneral extends Command
{
    protected $signature = 'control-general:importar-excel
                            {archivo : Ruta del Excel Control General (.xlsm)}
                            {--confirmar : Guardar los cambios (sin esto solo muestra el resumen)}';

    protected $description = 'Importa al sistema el trabajo manual del Excel Control General (asesor, cuadrilla, técnico, observaciones, trámites, habilitadas)';

    public function handle(ControlGeneralImportador $importador): int
    {
        $archivo = $this->argument('archivo');
        if (!is_file($archivo)) {
            $this->error("No existe el archivo: {$archivo}");
            return self::FAILURE;
        }

        $guardar = (bool) $this->option('confirmar');
        $this->info($guardar ? 'Importando…' : 'Simulación (no se guarda nada; agrega --confirmar para guardar)…');

        $r = $importador->importar($archivo, $guardar);

        $this->table(['Etapa', 'Solicitudes'], collect(ControlGeneral::ETAPAS)
            ->map(fn ($etiqueta, $etapa) => [$etiqueta, $r['por_etapa'][$etapa] ?? 0])
            ->values()->all());

        $this->line("Filas leídas del Excel: {$r['leidas']}");
        $this->line("Encontradas en el sistema: {$r['importadas']}");
        if ($guardar) {
            $this->line("Movidas por marcas pendientes (Construida/Habilitado/Anular): {$r['movidas_por_marca']}");
        }

        if ($r['no_encontradas']) {
            $this->warn(count($r['no_encontradas']) . ' solicitudes del Excel no están en el sistema (carga primero el Excel del portal que las tenga):');
            $this->line(implode(', ', array_slice($r['no_encontradas'], 0, 50)) . (count($r['no_encontradas']) > 50 ? ' …' : ''));
        }

        return self::SUCCESS;
    }
}
