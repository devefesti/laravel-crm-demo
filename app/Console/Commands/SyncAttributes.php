<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MagentoManager;

class SyncAttributes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'syncattr:cron';

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
            $manager->syncAttributes();
    
            $this->info('Sync attributes command executed successfully!');
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            \Log::error('Error executing sync status command: ' . $e->getMessage());
        }
    
        return 0;
    }
}
