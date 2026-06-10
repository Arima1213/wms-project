<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

class CheckNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run all notification checks and dispatch alerts';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService)
    {
        $this->info('Starting notification checks...');
        
        $notificationService->runAllChecks();
        
        $this->info('Notification checks completed.');
    }
}
