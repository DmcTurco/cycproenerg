<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('queue:work --stop-when-empty --tries=3 --max-time=60')
                ->everyMinute()
                ->withoutOverlapping()
                ->runInBackground()
                ->before(function () {
                    Log::info('Iniciando worker de cola', [
                        'memory' => memory_get_usage(true) / 1024 / 1024 . 'MB',
                        'time' => now()->toDateTimeString()
                    ]);
                })
                ->onSuccess(function () {
                    $jobsCount = DB::table('jobs')->count();
                    Log::info('Worker procesó jobs exitosamente', [
                        'jobs_pendientes' => $jobsCount,
                        'memory' => memory_get_usage(true) / 1024 / 1024 . 'MB',
                        'time' => now()->toDateTimeString()
                    ]);
                })
                ->onFailure(function () {
                    $jobsCount = DB::table('jobs')->count();
                    Log::error('El worker falló al iniciar', [
                        'jobs_pendientes' => $jobsCount,
                        'memory' => memory_get_usage(true) / 1024 / 1024 . 'MB',
                        'error_details' => error_get_last(),
                        'time' => now()->toDateTimeString()
                    ]);
                });

        // Limpiar jobs fallidos antiguos cada hora
        $schedule->command('queue:prune-failed --hours=24')
                ->hourly();

        // Reiniciar el worker cada 12 horas
        $schedule->command('queue:restart')
                ->twiceDaily(1, 13);
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
