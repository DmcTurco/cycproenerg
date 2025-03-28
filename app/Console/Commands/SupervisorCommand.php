<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class SupervisorCommand extends Command
{
    protected $signature = 'queue:process';
    protected $description = 'Procesa los trabajos pendientes en la cola';

    public function handle()
    {
        if (DB::table('jobs')->count() > 0) {
            $this->info('Procesando trabajos pendientes...');

            Artisan::call('queue:work', [
                '--stop-when-empty' => true,
                '--memory' => '256',
                '--timeout' => 300,
                '--tries' => 3,
                '--quiet' => true
            ]);
            $this->info('Proceso de cola iniciado en segundo plano.');
        } else {
            $this->info('No hay trabajos pendientes para procesar.');
        }

        return 0;
    }
}
