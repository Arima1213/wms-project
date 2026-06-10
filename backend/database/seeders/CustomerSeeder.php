<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            ['code' => 'CUST-001', 'name' => 'PT Retail Maju Jaya',       'contact_person' => 'Rudi Hartono',  'phone' => '021-7771001', 'email' => 'rudi@retailmaju.co.id', 'city' => 'Jakarta', 'province' => 'DKI Jakarta', 'customer_type' => 'retail'],
            ['code' => 'CUST-002', 'name' => 'Toko Sukses Selalu',        'contact_person' => 'Maya Indah',    'phone' => '031-7772002', 'email' => 'maya@sukses.com', 'city' => 'Surabaya', 'province' => 'Jawa Timur', 'customer_type' => 'retail'],
            ['code' => 'CUST-003', 'name' => 'PT Distribusi Lancar',      'contact_person' => 'Fajar Nugroho', 'phone' => '022-7773003', 'email' => 'fajar@distribusi-lancar.com', 'city' => 'Bandung', 'province' => 'Jawa Barat', 'customer_type' => 'distributor'],
            ['code' => 'CUST-004', 'name' => 'CV Aneka Niaga Bersama',    'contact_person' => 'Sri Wahyuni',   'phone' => '061-7774004', 'email' => 'sri@anekaniaga.com', 'city' => 'Medan', 'province' => 'Sumatera Utara', 'customer_type' => 'wholesale'],
            ['code' => 'CUST-005', 'name' => 'PT E-Commerce Indonesia',   'contact_person' => 'Dimas Ardian',  'phone' => '021-7775005', 'email' => 'dimas@ecommerce.co.id', 'city' => 'Jakarta', 'province' => 'DKI Jakarta', 'customer_type' => 'ecommerce'],
        ];

        foreach ($customers as $c) {
            Customer::create(array_merge($c, [
                'address' => 'Jl. ' . $c['city'] . ' No. ' . fake()->buildingNumber(),
                'postal_code' => fake()->postcode(),
                'tax_id' => fake()->numerify('##.###.###.#-###.###'),
            ]));
        }

        $this->command->info('Customers seeded: ' . Customer::count() . ' customers');
    }
}
