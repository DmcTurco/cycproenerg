<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * OBSOLETO (13/09/2026): renombrado a RevisarColaPendienteCommand.php
 * (signature `queue:revisar-pendientes`) — "Supervisor" se confundía con la
 * herramienta real de Linux del mismo nombre (que hace algo distinto:
 * mantener un proceso corriendo para siempre). `routes/console.php` ya
 * apunta al comando nuevo, así que este archivo ya no lo usa nada.
 *
 * No se pudo borrar este archivo desde la sesión de Claude (sin acceso para
 * borrar archivos en esta máquina) — puedes borrarlo tú cuando quieras.
 */
class SupervisorCommand extends Command
{
    protected $signature = 'supervisor:obsoleto';
    protected $description = 'OBSOLETO — ver RevisarColaPendienteCommand (queue:revisar-pendientes). Este archivo se puede borrar.';

    public function handle(): int
    {
        $this->warn('Este comando ya no se usa. Ver RevisarColaPendienteCommand (queue:revisar-pendientes).');

        return self::SUCCESS;
    }
}
