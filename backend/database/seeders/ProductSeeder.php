<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Uom;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $catElec = ProductCategory::where('code', 'ELEC')->first();
        $catPack = ProductCategory::where('code', 'PACK')->first();
        $catRawm = ProductCategory::where('code', 'RAWM')->first();
        $catFmcg = ProductCategory::where('code', 'FMCG')->first();
        $catFood = ProductCategory::where('code', 'FOOD')->first();
        $catChem = ProductCategory::where('code', 'CHEM')->first();

        $pcs = Uom::where('code', 'PCS')->first();
        $kg = Uom::where('code', 'KG')->first();
        $box = Uom::where('code', 'BOX')->first();
        $roll = Uom::where('code', 'ROLL')->first();
        $ltr = Uom::where('code', 'LTR')->first();

        $products = [
            // Electronics
            ['code' => 'PRD-0001', 'sku' => 'ELEC-001', 'name' => 'Kabel USB Type-C 1M',       'category_id' => $catElec->id, 'unit_id' => $pcs->id,  'min_stock' => 100,  'max_stock' => 1000,  'weight_kg' => 0.05,  'barcode' => '8999901000012'],
            ['code' => 'PRD-0002', 'sku' => 'ELEC-002', 'name' => 'Adapter Listrik 5V 2A',      'category_id' => $catElec->id, 'unit_id' => $pcs->id,  'min_stock' => 50,   'max_stock' => 500,   'weight_kg' => 0.08,  'barcode' => '8999901000029'],
            ['code' => 'PRD-0003', 'sku' => 'ELEC-003', 'name' => 'Mouse Wireless Bluetooth',    'category_id' => $catElec->id, 'unit_id' => $pcs->id,  'min_stock' => 30,   'max_stock' => 300,   'weight_kg' => 0.12,  'barcode' => '8999901000036'],
            ['code' => 'PRD-0004', 'sku' => 'ELEC-004', 'name' => 'Keyboard Mechanical RGB',    'category_id' => $catElec->id, 'unit_id' => $pcs->id,  'min_stock' => 20,   'max_stock' => 200,   'weight_kg' => 0.85,  'barcode' => '8999901000043'],

            // Packaging
            ['code' => 'PRD-0005', 'sku' => 'PACK-001', 'name' => 'Kotak Karton 30x30x30',       'category_id' => $catPack->id, 'unit_id' => $pcs->id,  'min_stock' => 200,  'max_stock' => 2000,  'length_cm' => 30, 'width_cm' => 30, 'height_cm' => 30, 'barcode' => '8999901000050'],
            ['code' => 'PRD-0006', 'sku' => 'PACK-002', 'name' => 'Bubble Wrap Roll 50M',        'category_id' => $catPack->id, 'unit_id' => $roll->id, 'min_stock' => 20,   'max_stock' => 100,   'barcode' => '8999901000067'],
            ['code' => 'PRD-0007', 'sku' => 'PACK-003', 'name' => 'Stretch Film 500M',           'category_id' => $catPack->id, 'unit_id' => $roll->id, 'min_stock' => 15,   'max_stock' => 80,    'barcode' => '8999901000074'],
            ['code' => 'PRD-0008', 'sku' => 'PACK-004', 'name' => 'Label Stiker 100x150mm',      'category_id' => $catPack->id, 'unit_id' => $pcs->id,  'min_stock' => 500,  'max_stock' => 5000,  'barcode' => '8999901000081'],

            // Raw Materials
            ['code' => 'PRD-0009', 'sku' => 'RAWM-001', 'name' => 'Plastik PE 0.5mm',            'category_id' => $catRawm->id, 'unit_id' => $kg->id,   'min_stock' => 500,  'max_stock' => 5000,  'track_batch' => true, 'track_expiry' => true, 'barcode' => '8999901000098'],
            ['code' => 'PRD-0010', 'sku' => 'RAWM-002', 'name' => 'Resin PP Hitam',              'category_id' => $catRawm->id, 'unit_id' => $kg->id,   'min_stock' => 1000, 'max_stock' => 10000, 'track_batch' => true, 'barcode' => '8999901000104'],

            // FMCG
            ['code' => 'PRD-0011', 'sku' => 'FMCG-001', 'name' => 'Susu UHT Full Cream 1L',      'category_id' => $catFmcg->id, 'unit_id' => $box->id,  'min_stock' => 100,  'max_stock' => 2000,  'track_batch' => true, 'track_expiry' => true, 'barcode' => '8999901000111'],
            ['code' => 'PRD-0012', 'sku' => 'FMCG-002', 'name' => 'Mie Instan Goreng',           'category_id' => $catFmcg->id, 'unit_id' => $box->id,  'min_stock' => 200,  'max_stock' => 5000,  'track_batch' => true, 'track_expiry' => true, 'barcode' => '8999901000128'],
            ['code' => 'PRD-0013', 'sku' => 'FMCG-003', 'name' => 'Air Mineral 600ml',           'category_id' => $catFmcg->id, 'unit_id' => $box->id,  'min_stock' => 300,  'max_stock' => 3000,  'track_batch' => true, 'barcode' => '8999901000135'],
            ['code' => 'PRD-0014', 'sku' => 'FMCG-004', 'name' => 'Kecap Manis 135ml',           'category_id' => $catFmcg->id, 'unit_id' => $box->id,  'min_stock' => 100,  'max_stock' => 1000,  'track_batch' => true, 'track_expiry' => true, 'barcode' => '8999901000142'],

            // Food & Beverage
            ['code' => 'PRD-0015', 'sku' => 'FOOD-001', 'name' => 'Beras Premium 5kg',           'category_id' => $catFood->id, 'unit_id' => $pcs->id,  'min_stock' => 50,   'max_stock' => 500,   'weight_kg' => 5.0,   'track_batch' => true, 'barcode' => '8999901000159'],
            ['code' => 'PRD-0016', 'sku' => 'FOOD-002', 'name' => 'Minyak Goreng 2L',            'category_id' => $catFood->id, 'unit_id' => $ltr->id,  'min_stock' => 100,  'max_stock' => 800,   'track_batch' => true, 'track_expiry' => true, 'barcode' => '8999901000166'],

            // Chemicals
            ['code' => 'PRD-0017', 'sku' => 'CHEM-001', 'name' => 'Desinfektan 5L',              'category_id' => $catChem->id, 'unit_id' => $ltr->id,  'min_stock' => 20,   'max_stock' => 200,   'product_type' => 'hazmat', 'track_batch' => true, 'barcode' => '8999901000173'],
            ['code' => 'PRD-0018', 'sku' => 'CHEM-002', 'name' => 'Pelarut Industri A-100',      'category_id' => $catChem->id, 'unit_id' => $ltr->id,  'min_stock' => 50,   'max_stock' => 400,   'product_type' => 'hazmat', 'track_batch' => true, 'barcode' => '8999901000180'],
        ];

        $i = 0;
        foreach ($products as $p) {
            Product::create(array_merge(['is_active' => true], $p));
            $i++;
        }

        $this->command->info("Products seeded: {$i} products");
    }
}
