<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AuditLogSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@wms.local')->first();
        $manager = User::where('email', 'budi@wms.local')->first();
        $operator = User::where('email', 'siti@wms.local')->first();

        $logs = [
            ['user' => 'admin', 'action' => 'login',            'entity' => 'user',     'entity_id' => null,                'old' => null,                                                  'new' => json_encode(['ip' => '192.168.1.100', 'user_agent' => 'Mozilla/5.0']),                                  'created' => now()->subDays(7)],
            ['user' => 'admin', 'action' => 'user.created',     'entity' => 'user',     'entity_id' => $manager?->id,       'old' => null,                                                  'new' => json_encode(['name' => 'Budi Santoso', 'email' => 'budi@wms.local', 'role' => 'manager']),             'created' => now()->subDays(7)],
            ['user' => 'admin', 'action' => 'inbound.received', 'entity' => 'inbound',  'entity_id' => 1,                   'old' => json_encode(['status' => 'pending']),                   'new' => json_encode(['status' => 'received', 'received_date' => now()->subDays(7)->format('Y-m-d')]),        'created' => now()->subDays(7)],
            ['user' => 'manager', 'action' => 'outbound.shipped', 'entity' => 'outbound', 'entity_id' => 2,                  'old' => json_encode(['status' => 'picking']),                   'new' => json_encode(['status' => 'shipped', 'shipped_date' => now()->subDays(6)->format('Y-m-d')]),          'created' => now()->subDays(6)],
            ['user' => 'admin', 'action' => 'inventory.adjust', 'entity' => 'inventory', 'entity_id' => 1,                   'old' => json_encode(['quantity' => 200]),                       'new' => json_encode(['quantity' => 205]),                                                                        'created' => now()->subDays(5)],
            ['user' => 'operator', 'action' => 'stock_opname.submitted', 'entity' => 'stock_opname', 'entity_id' => 1,       'old' => json_encode(['status' => 'in_progress']),               'new' => json_encode(['status' => 'submitted']),                                                                  'created' => now()->subDays(4)],
            ['user' => 'admin', 'action' => 'stock_opname.approved', 'entity' => 'stock_opname', 'entity_id' => 1,            'old' => json_encode(['status' => 'submitted']),                 'new' => json_encode(['status' => 'approved', 'approved_at' => now()->subDays(4)->format('Y-m-d')]),           'created' => now()->subDays(4)],
            ['user' => 'admin', 'action' => 'setting.updated',  'entity' => 'setting',  'entity_id' => 1,                   'old' => json_encode(['value' => 'PT WMS Logistics']),           'new' => json_encode(['value' => 'PT WMS Logistics Indonesia']),                                                 'created' => now()->subDays(3)],
            ['user' => 'manager', 'action' => 'transfer.approved', 'entity' => 'transfer', 'entity_id' => 1,                  'old' => json_encode(['status' => 'pending']),                   'new' => json_encode(['status' => 'approved']),                                                                   'created' => now()->subDays(2)],
            ['user' => 'operator', 'action' => 'login',          'entity' => 'user',     'entity_id' => null,                'old' => null,                                                  'new' => json_encode(['ip' => '192.168.1.50', 'user_agent' => 'Mozilla/5.0']),                                   'created' => now()->subDay()],
        ];

        $users = [
            'admin' => $admin,
            'manager' => $manager,
            'operator' => $operator,
        ];

        $inserts = [];
        foreach ($logs as $log) {
            $user = $users[$log['user']] ?? $admin;
            $inserts[] = [
                'user_id' => $user?->id,
                'action' => $log['action'],
                'entity_type' => $log['entity'],
                'entity_id' => $log['entity_id'],
                'old_values' => $log['old'],
                'new_values' => $log['new'],
                'ip_address' => '192.168.1.' . rand(10, 200),
                'created_at' => $log['created'],
            ];
        }
        DB::table('audit_logs')->insert($inserts);

        $this->command->info('Audit logs seeded: ' . count($logs) . ' log entries');
    }
}
