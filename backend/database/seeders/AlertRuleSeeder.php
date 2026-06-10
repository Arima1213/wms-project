<?php

namespace Database\Seeders;

use App\Models\AlertRule;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AlertRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rules = [
            [
                'name' => 'Low Stock Alert',
                'type' => 'low_stock',
                'config' => [],
                'is_active' => true,
            ],
            [
                'name' => 'Expiring Products Alert',
                'type' => 'expiring_products',
                'config' => ['days' => 30],
                'is_active' => true,
            ],
            [
                'name' => 'Inbound Overdue Alert',
                'type' => 'inbound_overdue',
                'config' => ['hours' => 24],
                'is_active' => true,
            ],
            [
                'name' => 'Outbound Overdue Alert',
                'type' => 'outbound_overdue',
                'config' => ['hours' => 24],
                'is_active' => true,
            ],
        ];

        foreach ($rules as $rule) {
            AlertRule::updateOrCreate(
                ['type' => $rule['type']],
                $rule
            );
        }
    }
}
