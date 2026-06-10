<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $wh1 = Warehouse::where('code', 'WH001')->first();
        $wh2 = Warehouse::where('code', 'WH002')->first();

        $admin = User::create([
            'name' => 'Administrator',
            'email' => 'admin@wms.local',
            'password' => Hash::make('password123'),
            'phone' => '0812-3456-7890',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('super_admin');

        $manager = User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@wms.local',
            'password' => Hash::make('password123'),
            'phone' => '0812-3456-7891',
            'is_active' => true,
            'default_warehouse_id' => $wh1?->id,
            'email_verified_at' => now(),
        ]);
        $manager->assignRole('manager');

        $manager2 = User::create([
            'name' => 'Dewi Lestari',
            'email' => 'dewi@wms.local',
            'password' => Hash::make('password123'),
            'phone' => '0812-3456-7892',
            'is_active' => true,
            'default_warehouse_id' => $wh2?->id,
            'email_verified_at' => now(),
        ]);
        $manager2->assignRole('manager');

        $operator = User::create([
            'name' => 'Siti Rahayu',
            'email' => 'siti@wms.local',
            'password' => Hash::make('password123'),
            'phone' => '0812-3456-7893',
            'is_active' => true,
            'default_warehouse_id' => $wh1?->id,
            'email_verified_at' => now(),
        ]);
        $operator->assignRole('operator');

        $operator2 = User::create([
            'name' => 'Ahmad Fauzi',
            'email' => 'ahmad@wms.local',
            'password' => Hash::make('password123'),
            'phone' => '0812-3456-7894',
            'is_active' => true,
            'default_warehouse_id' => $wh1?->id,
            'email_verified_at' => now(),
        ]);
        $operator2->assignRole('operator');

        $operator3 = User::create([
            'name' => 'Rina Wijaya',
            'email' => 'rina@wms.local',
            'password' => Hash::make('password123'),
            'phone' => '0812-3456-7895',
            'is_active' => true,
            'default_warehouse_id' => $wh2?->id,
            'email_verified_at' => now(),
        ]);
        $operator3->assignRole('operator');

        $viewer = User::create([
            'name' => 'Tamu Viewer',
            'email' => 'viewer@wms.local',
            'password' => Hash::make('password123'),
            'phone' => null,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $viewer->assignRole('viewer');

        $this->command->info('Users seeded: admin, 2 managers, 3 operators, 1 viewer');
    }
}
