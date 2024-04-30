<?php

namespace App\Console\Commands;
use App\Models\MagentoManager;
use Illuminate\Console\Command;

class SyncOrderStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'syncstatus:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
             $manager = new MagentoManager();
             $manager->updateOrderStatus();
     
             $this->info('Sync order status command executed successfully!');
         } catch (\Exception $e) {
             $this->error('Error: ' . $e->getMessage());
             \Log::error('Error executing sync status command: ' . $e->getMessage());
         }
     
         return 0;
    }
}
