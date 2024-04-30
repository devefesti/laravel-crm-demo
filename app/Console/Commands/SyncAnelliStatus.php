<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MagentoManager;

class SyncAnelliStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'syncanelli:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description anelli status';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $manager = new MagentoManager();
            $manager->syncAnelliStatus();
    
            $this->info('Sync anelli status command executed successfully!');
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            \Log::error('Error executing sync status command: ' . $e->getMessage());
        }
    
        return 0;
    }
}
