<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            ['code' => 'SUP-001', 'name' => 'PT Elektronika Nusantara', 'contact_person' => 'Hendra Gunawan', 'phone' => '021-5551001', 'email' => 'hendra@elektronika.co.id', 'city' => 'Jakarta', 'province' => 'DKI Jakarta'],
            ['code' => 'SUP-002', 'name' => 'CV Packaging Jaya',        'contact_person' => 'Lily Susanti',    'phone' => '031-5552002', 'email' => 'lily@packagingjaya.co.id', 'city' => 'Surabaya', 'province' => 'Jawa Timur'],
            ['code' => 'SUP-003', 'name' => 'PT Kimia Indah',           'contact_person' => 'Agus Priyono',    'phone' => '022-5553003', 'email' => 'agus@kimia-indah.co.id', 'city' => 'Bandung', 'province' => 'Jawa Barat'],
            ['code' => 'SUP-004', 'name' => 'PT Segar Makmur Sentosa',  'contact_person' => 'Dian Permata',    'phone' => '061-5554004', 'email' => 'dian@segarmakmur.co.id', 'city' => 'Medan', 'province' => 'Sumatera Utara'],
            ['code' => 'SUP-005', 'name' => 'PT Global Sumber Makmur',  'contact_person' => 'Bambang Wijaya',  'phone' => '021-5555005', 'email' => 'bambang@globalsumber.co.id', 'city' => 'Jakarta', 'province' => 'DKI Jakarta'],
            ['code' => 'SUP-006', 'name' => 'UD Sinar Abadi',           'contact_person' => 'Slamet Riyadi',   'phone' => '0274-5556006', 'email' => 'slamet@sinarabadi.co.id', 'city' => 'Yogyakarta', 'province' => 'DI Yogyakarta'],
        ];

        foreach ($suppliers as $s) {
            Supplier::create(array_merge($s, [
                'address' => 'Jl. ' . $s['city'] . ' No. ' . fake()->buildingNumber(),
                'postal_code' => fake()->postcode(),
            ]));
        }

        $this->command->info('Suppliers seeded: ' . Supplier::count() . ' suppliers');
    }
}
