<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('queue:process')
            ->everyMinute()
            ->runInBackground()
            ->withoutOverlapping(5) // Evita que múltiples instancias se ejecuten en paralelo
            ->onOneServer(); // Evita que múltiples servidores lo ejecuten simultáneamente
    
        $schedule->command('queue:prune-failed --hours=24')->hourly();
        $schedule->command('queue:restart')->everyFourHours();
    
        // Log para monitorear memoria cada 6 horas
        $schedule->call(function () {
            Log::info('Memoria usada por Laravel:', ['memory' => $this->formatMemory()]);
        })->everySixHours();
    }
    
    private function formatMemory(): string
    {
        return round(memory_get_usage(true) / 1024 / 1024, 2) . 'MB';
    }
    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
