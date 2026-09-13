<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Antes se llamaba "SupervisorCommand" — se renombró (13/09/2026) porque ese
 * nombre se confundía con Supervisor, la herramienta real de Linux (que hace
 * algo distinto: mantener un proceso corriendo para siempre). Este comando
 * NO es eso: es la red de seguridad que revisa, cada minuto (disparado por
 * el scheduler de routes/console.php), si quedó algún Excel pendiente sin
 * procesar — por si el lanzamiento inmediato que hace
 * ClientController::change() al subir el archivo falló por algún motivo — y
 * si hay uno, lo procesa. Ver docs/modulos/control-interno.md para la
 * explicación completa del flujo (subida → cola → este comando).
 */
class RevisarColaPendienteCommand extends Command
{
    protected $signature = 'queue:revisar-pendientes';
    protected $description = 'Revisa la cola de trabajos pendientes (ej. Excel sin procesar) y los procesa si hay alguno';

    public function handle()
    {
        $this->info('Revisando la cola de pendientes...');

        // Este comando NO procesa el Excel él mismo — solo lanza a OTRO
        // proceso (`queue:work`) para que lo haga, y no se queda esperando a
        // que termine.
        //
        // ¿Por qué no procesarlo aquí mismo, directo? Porque a este comando
        // lo dispara el scheduler cada minuto, y si se quedara esperando a
        // que `queue:work` termine de procesar un Excel grande (puede tardar
        // varios minutos), el scheduler pensaría que este comando sigue
        // "trabajando" y no podría lanzar el siguiente chequeo a tiempo. El
        // `&` al final del comando de abajo es lo que le dice a Linux
        // "ejecuta esto aparte, en paralelo, y devuélveme el control ya" —
        // así este comando termina en milisegundos (solo avisó al otro
        // proceso) en vez de tardar lo que tarde el Excel completo.
        //
        // Las opciones de `queue:work`:
        //   --stop-when-empty : procesa lo que haya en la cola y se apaga
        //                       solo (si no, se quedaría corriendo para
        //                       siempre esperando más trabajos, y este
        //                       comando lanzaría uno nuevo cada minuto sin
        //                       necesidad, acumulando procesos).
        //   --memory=256      : si llega a usar más de 256MB de memoria, se
        //                       reinicia solo (protección contra fugas de
        //                       memoria en cargas muy grandes).
        //   --timeout=300     : si UN trabajo tarda más de 5 minutos, se
        //                       da por fallido y se reintenta (no se queda
        //                       colgado para siempre).
        //   --tries=3         : reintentos si un trabajo falla — redundante
        //                       con el $tries=3 que ya tiene ProcessExcelJob,
        //                       pero no hace daño tenerlo en los dos lados.
        //   --quiet           : no imprime nada en pantalla (nadie está
        //                       mirando esta terminal, corre solo).
        $output = [];
        $returnCode = 0;
        exec("php artisan queue:work --stop-when-empty --memory=256 --timeout=300 --tries=3 --quiet > /dev/null 2>&1 &", $output, $returnCode);

        // OJO: por el `&` de arriba, `exec()` no espera a que `queue:work`
        // termine — vuelve de inmediato. Por eso $returnCode acá solo dice
        // si el sistema operativo logró ARRANCAR ese proceso aparte, no si
        // el Excel se terminó de procesar bien o mal (eso se ve en la tabla
        // `logs`, no acá).
        if ($returnCode !== 0) {
            Log::error('Error al lanzar el procesamiento de la cola', ['output' => $output]);
            $this->error('Error al lanzar el procesamiento de la cola.');
        }

        return self::SUCCESS;
    }
}
