<?php

namespace App\Console\Commands;

use App\Services\WebhookService;
use Illuminate\Console\Command;

class ProcessWebhookRetries extends Command
{
    protected $signature = 'webhooks:process-retries';
    protected $description = 'Process pending webhook delivery retries';

    public function handle(WebhookService $webhookService): int
    {
        $this->info('Processing pending webhook retries...');

        $processed = $webhookService->processRetries();

        $this->info("Processed {$processed} pending retries.");

        return Command::SUCCESS;
    }
}
