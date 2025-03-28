<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SupervisorCommand extends Command
{
    protected $signature = 'queue:process';
    protected $description = 'Procesa los trabajos pendientes en la cola';

    public function handle()
    {
        $this->info('Iniciando procesamiento de la cola...');

        // Ejecutar en segundo plano
        $output = [];
        $returnCode = 0;
        exec("php artisan queue:work --stop-when-empty --memory=256 --timeout=300 --tries=3 --quiet > /dev/null 2>&1 &", $output, $returnCode);

        // Solo registrar si hay un error
        if ($returnCode !== 0) {
            Log::error('Error al procesar la cola', ['output' => $output]);
            $this->error('Error al procesar la cola.');
        }

        return 0;
    }
}
