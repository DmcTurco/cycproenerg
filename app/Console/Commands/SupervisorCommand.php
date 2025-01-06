<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SupervisorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:supervisor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Supervise and process queue jobs silently';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        while (true) {
            if (DB::table('jobs')->count() > 0) {
                $this->call('queue:work', [
                    '--stop-when-empty' => true,
                    '--quiet' => true
                ]);
            }
            sleep(60);
        }
    }
}
