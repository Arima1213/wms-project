<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            ['code' => 'SUP-001', 'name' => 'PT Elektronika Nusantara', 'contact_name' => 'Hendra Gunawan', 'phone' => '021-5551001', 'email' => 'hendra@elektronika.co.id', 'city' => 'Jakarta'],
            ['code' => 'SUP-002', 'name' => 'CV Packaging Jaya',        'contact_name' => 'Lily Susanti',    'phone' => '031-5552002', 'email' => 'lily@packagingjaya.co.id', 'city' => 'Surabaya'],
            ['code' => 'SUP-003', 'name' => 'PT Kimia Indah',           'contact_name' => 'Agus Priyono',    'phone' => '022-5553003', 'email' => 'agus@kimia-indah.co.id', 'city' => 'Bandung'],
            ['code' => 'SUP-004', 'name' => 'PT Segar Makmur Sentosa',  'contact_name' => 'Dian Permata',    'phone' => '061-5554004', 'email' => 'dian@segarmakmur.co.id', 'city' => 'Medan'],
            ['code' => 'SUP-005', 'name' => 'PT Global Sumber Makmur',  'contact_name' => 'Bambang Wijaya',  'phone' => '021-5555005', 'email' => 'bambang@globalsumber.co.id', 'city' => 'Jakarta'],
            ['code' => 'SUP-006', 'name' => 'UD Sinar Abadi',           'contact_name' => 'Slamet Riyadi',   'phone' => '0274-5556006', 'email' => 'slamet@sinarabadi.co.id', 'city' => 'Yogyakarta'],
        ];

        // Use firstOrCreate for idempotency
        foreach ($suppliers as $s) {
            Supplier::firstOrCreate(
                ['code' => $s['code']],
                array_merge($s, [
                    'address' => 'Jl. ' . $s['city'] . ' No. ' . fake()->buildingNumber(),
                ])
            );
        }

        $this->command->info('Suppliers seeded: ' . Supplier::count() . ' suppliers');
    }
}
