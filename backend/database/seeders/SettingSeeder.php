<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'company.name',        'value' => 'PT WMS Logistics Indonesia',   'group' => 'company'],
            ['key' => 'company.address',     'value' => 'Jl. Industri Raya No. 1, Jakarta', 'group' => 'company'],
            ['key' => 'company.phone',       'value' => '021-5550000',                   'group' => 'company'],
            ['key' => 'company.email',       'value' => 'info@wmslogistics.co.id',       'group' => 'company'],
            ['key' => 'company.tax_id',      'value' => '01.234.567.8-999.000',          'group' => 'company'],
            ['key' => 'notification.email',  'value' => 'notif@wmslogistics.co.id',      'group' => 'notification'],
            ['key' => 'inventory.low_stock_threshold', 'value' => '10',                  'group' => 'inventory'],
            ['key' => 'inventory.auto_reserve',        'value' => 'true',                'group' => 'inventory'],
            ['key' => 'document.inbound_prefix',       'value' => 'INB',                 'group' => 'document'],
            ['key' => 'document.outbound_prefix',      'value' => 'OUT',                 'group' => 'document'],
            ['key' => 'document.transfer_prefix',      'value' => 'TRF',                 'group' => 'document'],
            ['key' => 'document.opname_prefix',        'value' => 'SO',                  'group' => 'document'],
            ['key' => 'storage.minio_bucket',          'value' => 'wms-documents',        'group' => 'storage'],
            ['key' => 'planogram.default_grid',        'value' => '50',                  'group' => 'planogram'],
        ];

        foreach ($settings as $s) {
            Setting::create($s);
        }

        $this->command->info('Settings seeded: ' . count($settings) . ' settings');
    }
}
