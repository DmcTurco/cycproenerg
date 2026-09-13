<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Schedule
|--------------------------------------------------------------------------
|
| Antes vivía en app/Console/Kernel.php::schedule() (Laravel 12 ya no usa
| esa clase; el propio artisan carga y registra este archivo).
|
*/

// Red de seguridad: revisa cada minuto si quedó algún Excel pendiente sin
// procesar (por si el lanzamiento inmediato de ClientController::change()
// falló) y, si hay uno, lo procesa. Antes se llamaba "queue:process"
// (clase SupervisorCommand) — se renombró (13/09/2026) porque ese nombre se
// confundía con Supervisor, la herramienta real de Linux (que hace algo
// distinto: mantener un proceso corriendo para siempre).
Schedule::command('queue:revisar-pendientes')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->runInBackground()
    ->onOneServer();

Schedule::command('queue:prune-failed --hours=24')->hourly();
Schedule::command('queue:restart')->everyFourHours();

// CI-5 (13/09/2026): refresca el caché de indicadores (DESFACE/SEMÁFORO/DÍAS
// HÁBILES/FUERA DE PLAZO) de todas las solicitudes — ver el docblock de
// RecalcularIndicadoresControlInterno para por qué hace falta corrarlo aparte
// de cada carga/edición individual. Fuera de horario de oficina para no
// competir con las cargas de Excel del día.
Schedule::command('control-interno:recalcular-indicadores')->dailyAt('01:00');

Schedule::call(function () {
    Log::info('Memoria usada por Laravel:', [
        'memory' => round(memory_get_usage(true) / 1024 / 1024, 2) . 'MB',
    ]);
})->everySixHours();
