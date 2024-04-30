<?php

namespace App\Console\Commands;
use App\Models\MagentoManager;
use Illuminate\Console\Command;

class SyncProductQuantities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'syncquantities:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Allinea le quantità dal catalogo di magento al gestionale';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
             $manager = new MagentoManager();
             $manager->syncProdsQuantities();
     
             $this->info('Sync product quantities command executed successfully!');
         } catch (\Exception $e) {
             $this->error('Error: ' . $e->getMessage());
             \Log::error('Error executing sync quantities command: ' . $e->getMessage());
         }
     
         return 0;
    }
}