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
        // Procesa las colas cada minuto si hay trabajos pendientes
        $schedule->call(function () {
            if (DB::table('jobs')->count() > 0) {
                Artisan::call('queue:work', [
                    '--stop-when-empty' => true,
                    '--quiet' => true
                ]);
            }
        })->everyMinute();
    
        $schedule->command('queue:prune-failed --hours=24')->hourly();
        $schedule->command('queue:restart')->everyFourHours();
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
