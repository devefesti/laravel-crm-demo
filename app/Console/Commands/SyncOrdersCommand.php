<?php

namespace App\Console\Commands;
//use Http\Efesti\Magento\MagentoSingleton;
use Illuminate\Console\Command;
use App\Models\MagentoManager;

class SyncordersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'syncorders:cron';

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
           /*  $singInstance = MagentoSingleton::getInstance();
            $singInstance::syncOrders(); */
            $manager = new MagentoManager();
            $manager->syncOrders();
    
            $this->info('Sync orders command executed successfully!');
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            \Log::error('Error executing sync orders command: ' . $e->getMessage());
        }
    
        return 0;
     
    }
}
