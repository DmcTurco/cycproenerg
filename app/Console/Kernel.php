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
        $schedule->command('queue:work --stop-when-empty --tries=3 --max-time=300')
                ->everyMinute()
                ->withoutOverlapping()
                ->runInBackground()
                ->before(function () {
                    $jobsCount = DB::table('jobs')->count();
                    if ($jobsCount > 0) {
                        Log::info('Iniciando worker de cola', [
                            'jobs_pendientes' => $jobsCount,
                            'memory' => $this->formatMemory()
                        ]);
                    }
                })
                ->onSuccess(function () {
                    $oneMinuteAgo = time() - 60;
                    $processedJobs = DB::table('jobs')
                        ->where('created_at', '>=', $oneMinuteAgo)
                        ->count();
    
                    $pendingJobs = DB::table('jobs')->count();
                    
                    if ($processedJobs > 0 || $pendingJobs > 0) {
                        Log::info('Estado del worker', [
                            'jobs_pendientes' => $pendingJobs,
                            'jobs_procesados' => $processedJobs,
                            'memory' => $this->formatMemory(),
                            'estado' => $pendingJobs > 0 ? 'procesando' : 'completado'
                        ]);
                    }
                })
                ->onFailure(function () {
                    $pendingJobs = DB::table('jobs')->count();
                    if ($pendingJobs > 0) {
                        Log::info('Worker en espera', [
                            'jobs_pendientes' => $pendingJobs,
                            'memory' => $this->formatMemory(),
                            'estado' => 'esperando'
                        ]);
                    }
                });
    
        // Mantener el resto igual
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
