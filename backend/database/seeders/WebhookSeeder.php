<?php

namespace Database\Seeders;

use App\Models\Webhook;
use App\Models\User;
use Illuminate\Database\Seeder;

class WebhookSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@wms.local')->first();
        if (!$admin) return;

        Webhook::create([
            'name' => 'ERP Integration',
            'url' => 'https://erp.company.local/webhook/wms',
            'secret' => hash('sha256', 'erp-secret-key-' . time()),
            'events' => ['inbound.received', 'outbound.shipped', 'transfer.completed', 'stock_opname.approved'],
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        Webhook::create([
            'name' => 'Notification Gateway',
            'url' => 'https://notif.company.local/webhook',
            'secret' => hash('sha256', 'notif-secret-key-' . time()),
            'events' => ['inbound.received', 'outbound.shipped'],
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        $this->command->info('Webhooks seeded: 2 webhooks');
    }
}
