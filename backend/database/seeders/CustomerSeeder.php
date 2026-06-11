<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            ['code' => 'CUST-001', 'name' => 'PT Retail Maju Jaya',       'contact_name' => 'Rudi Hartono',  'phone' => '021-7771001', 'email' => 'rudi@retailmaju.co.id', 'city' => 'Jakarta'],
            ['code' => 'CUST-002', 'name' => 'Toko Sukses Selalu',        'contact_name' => 'Maya Indah',    'phone' => '031-7772002', 'email' => 'maya@sukses.com', 'city' => 'Surabaya'],
            ['code' => 'CUST-003', 'name' => 'PT Distribusi Lancar',      'contact_name' => 'Fajar Nugroho', 'phone' => '022-7773003', 'email' => 'fajar@distribusi-lancar.com', 'city' => 'Bandung'],
            ['code' => 'CUST-004', 'name' => 'CV Aneka Niaga Bersama',    'contact_name' => 'Sri Wahyuni',   'phone' => '061-7774004', 'email' => 'sri@anekaniaga.com', 'city' => 'Medan'],
            ['code' => 'CUST-005', 'name' => 'PT E-Commerce Indonesia',   'contact_name' => 'Dimas Ardian',  'phone' => '021-7775005', 'email' => 'dimas@ecommerce.co.id', 'city' => 'Jakarta'],
        ];

        foreach ($customers as $c) {
            Customer::firstOrCreate(
                ['code' => $c['code']],
                array_merge(collect($c)->only(['code', 'name', 'contact_name', 'phone', 'email', 'city'])->toArray(), [
                    'address' => 'Jl. ' . $c['city'] . ' No. ' . fake()->buildingNumber(),
                ])
            );
        }

        $this->command->info('Customers seeded: ' . Customer::count() . ' customers');
    }
}
