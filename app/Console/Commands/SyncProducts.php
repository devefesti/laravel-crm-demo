<?php

namespace App\Console\Commands;
use App\Models\MagentoManager;
use Illuminate\Console\Command;

class SyncProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'syncprods:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Allinea i prodotti dal catalogo di magento al gestionale';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
             $manager = new MagentoManager();
             $manager->syncProducts();
     
             $this->info('Sync products command executed successfully!');
         } catch (\Exception $e) {
             $this->error('Error: ' . $e->getMessage());
             \Log::error('Error executing sync status command: ' . $e->getMessage());
         }
     
         return 0;
    }
}