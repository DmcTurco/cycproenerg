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

Schedule::command('queue:process')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->runInBackground()
    ->onOneServer();

Schedule::command('queue:prune-failed --hours=24')->hourly();
Schedule::command('queue:restart')->everyFourHours();

Schedule::call(function () {
    Log::info('Memoria usada por Laravel:', [
        'memory' => round(memory_get_usage(true) / 1024 / 1024, 2) . 'MB',
    ]);
})->everySixHours();
