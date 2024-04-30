<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MagentoManager;

class SyncCanceledAndClosed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'synccancclosed:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description canceled/closed status';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $manager = new MagentoManager();
            $manager->syncCanceledAndClosedOrders();
    
            $this->info('Sync canceled/closed status command executed successfully!');
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            \Log::error('Error executing sync canceled/closed command: ' . $e->getMessage());
        }
    
        return 0;
    }
}