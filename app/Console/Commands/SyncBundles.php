<?php

namespace App\Console\Commands;
use App\Models\MagentoManager;
use Illuminate\Console\Command;

class SyncBundles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'syncbundle:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Allinea i prodotti BUNDLE dal catalogo di magento al gestionale!';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
             $manager = new MagentoManager();
             $manager->syncBundleProds();
     
             $this->info('Sync bundles command executed successfully!');
         } catch (\Exception $e) {
             $this->error('Error: ' . $e->getMessage());
             \Log::error('Error executing sync bundles command: ' . $e->getMessage());
         }
     
         return 0;
    }
}