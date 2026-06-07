<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Inbound;
use App\Services\InboundService;

class TestReceive extends Command
{
    protected $signature = 'test:receive';
    protected $description = 'Test receive';

    public function handle(InboundService $service)
    {
        try {
            $inbound = Inbound::find(7);
            if (!$inbound) {
                $this->error('Inbound 7 not found');
                return;
            }
            $service->receive($inbound, 1);
            $this->info('Success');
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            $this->error($e->getFile() . ':' . $e->getLine());
        }
    }
}
