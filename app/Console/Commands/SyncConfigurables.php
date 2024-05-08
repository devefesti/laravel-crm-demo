<?php

namespace App\Console\Commands;
use App\Models\MagentoManager;
use Illuminate\Console\Command;

class SyncConfigurables extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'syncconfig:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Allinea i prodotti CONFIGURABLE dal catalogo di magento al gestionale';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
             $manager = new MagentoManager();
             $manager->syncConfigProducts();
     
             $this->info('Sync bundles command executed successfully!');
         } catch (\Exception $e) {
             $this->error('Error: ' . $e->getMessage());
             \Log::error('Error executing sync status command: ' . $e->getMessage());
         }
     
         return 0;
    }
}