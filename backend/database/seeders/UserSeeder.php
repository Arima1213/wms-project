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

        $admin = User::firstOrCreate(
            ['email' => 'admin@wms.local'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password123'),
                'phone' => '0812-3456-7890',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        if (!$admin->hasRole('super_admin')) $admin->assignRole('super_admin');

        $manager = User::firstOrCreate(
            ['email' => 'budi@wms.local'],
            [
                'name' => 'Budi Santoso',
                'password' => Hash::make('password123'),
                'phone' => '0812-3456-7891',
                'is_active' => true,
                'default_warehouse_id' => $wh1?->id,
                'email_verified_at' => now(),
            ]
        );
        if (!$manager->hasRole('manager')) $manager->assignRole('manager');

        $manager2 = User::firstOrCreate(
            ['email' => 'dewi@wms.local'],
            [
                'name' => 'Dewi Lestari',
                'password' => Hash::make('password123'),
                'phone' => '0812-3456-7892',
                'is_active' => true,
                'default_warehouse_id' => $wh2?->id,
                'email_verified_at' => now(),
            ]
        );
        if (!$manager2->hasRole('manager')) $manager2->assignRole('manager');

        $operator = User::firstOrCreate(
            ['email' => 'siti@wms.local'],
            [
                'name' => 'Siti Rahayu',
                'password' => Hash::make('password123'),
                'phone' => '0812-3456-7893',
                'is_active' => true,
                'default_warehouse_id' => $wh1?->id,
                'email_verified_at' => now(),
            ]
        );
        if (!$operator->hasRole('operator')) $operator->assignRole('operator');

        $operator2 = User::firstOrCreate(
            ['email' => 'ahmad@wms.local'],
            [
                'name' => 'Ahmad Fauzi',
                'password' => Hash::make('password123'),
                'phone' => '0812-3456-7894',
                'is_active' => true,
                'default_warehouse_id' => $wh1?->id,
                'email_verified_at' => now(),
            ]
        );
        if (!$operator2->hasRole('operator')) $operator2->assignRole('operator');

        $operator3 = User::firstOrCreate(
            ['email' => 'rina@wms.local'],
            [
                'name' => 'Rina Wijaya',
                'password' => Hash::make('password123'),
                'phone' => '0812-3456-7895',
                'is_active' => true,
                'default_warehouse_id' => $wh2?->id,
                'email_verified_at' => now(),
            ]
        );
        if (!$operator3->hasRole('operator')) $operator3->assignRole('operator');

        $viewer = User::firstOrCreate(
            ['email' => 'viewer@wms.local'],
            [
                'name' => 'Tamu Viewer',
                'password' => Hash::make('password123'),
                'phone' => null,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
        if (!$viewer->hasRole('viewer')) $viewer->assignRole('viewer');

        $this->command->info('Users seeded: admin, 2 managers, 3 operators, 1 viewer');
    }
}
