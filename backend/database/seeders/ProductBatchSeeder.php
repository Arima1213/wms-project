<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductBatch;
use Illuminate\Database\Seeder;

class ProductBatchSeeder extends Seeder
{
    public function run(): void
    {
        // Only create batches for products that track them
        $batchProducts = Product::where('track_batch', true)->get();

        $batchData = [
            // PRD-0009: Plastik PE 0.5mm
            ['sku' => 'RAWM-001', 'batches' => [
                ['batch_number' => 'B-20260501', 'expiry_date' => '2027-05-01', 'manufacture_date' => '2026-05-01', 'origin_country' => 'Indonesia', 'cost' => 8500],
                ['batch_number' => 'B-20260515', 'expiry_date' => '2027-05-15', 'manufacture_date' => '2026-05-15', 'origin_country' => 'Indonesia', 'cost' => 8400],
            ]],
            // PRD-0010: Resin PP Hitam
            ['sku' => 'RAWM-002', 'batches' => [
                ['batch_number' => 'B-20260601', 'manufacture_date' => '2026-06-01', 'origin_country' => 'Malaysia', 'cost' => 12500],
            ]],
            // PRD-0011: Susu UHT Full Cream 1L
            ['sku' => 'FMCG-001', 'batches' => [
                ['batch_number' => 'B-20260610-A', 'expiry_date' => '2026-09-10', 'manufacture_date' => '2026-06-10', 'origin_country' => 'Indonesia', 'cost' => 15500],
                ['batch_number' => 'B-20260615-A', 'expiry_date' => '2026-09-15', 'manufacture_date' => '2026-06-15', 'origin_country' => 'Indonesia', 'cost' => 15600],
            ]],
            // PRD-0012: Mie Instan Goreng
            ['sku' => 'FMCG-002', 'batches' => [
                ['batch_number' => 'B-20260520', 'expiry_date' => '2026-12-20', 'manufacture_date' => '2026-05-20', 'origin_country' => 'Indonesia', 'cost' => 3500],
                ['batch_number' => 'B-20260605', 'expiry_date' => '2026-12-05', 'manufacture_date' => '2026-06-05', 'origin_country' => 'Indonesia', 'cost' => 3400],
            ]],
            // PRD-0013: Air Mineral 600ml
            ['sku' => 'FMCG-003', 'batches' => [
                ['batch_number' => 'B-20260601-A', 'manufacture_date' => '2026-06-01', 'origin_country' => 'Indonesia', 'cost' => 2000],
            ]],
            // PRD-0014: Kecap Manis 135ml
            ['sku' => 'FMCG-004', 'batches' => [
                ['batch_number' => 'B-20260510', 'expiry_date' => '2027-05-10', 'manufacture_date' => '2026-05-10', 'origin_country' => 'Indonesia', 'cost' => 4500],
            ]],
            // PRD-0015: Beras Premium 5kg
            ['sku' => 'FOOD-001', 'batches' => [
                ['batch_number' => 'B-20260601', 'manufacture_date' => '2026-06-01', 'origin_country' => 'Indonesia', 'cost' => 65000],
                ['batch_number' => 'B-20260610', 'manufacture_date' => '2026-06-10', 'origin_country' => 'Indonesia', 'cost' => 64800],
            ]],
            // PRD-0016: Minyak Goreng 2L
            ['sku' => 'FOOD-002', 'batches' => [
                ['batch_number' => 'B-20260525', 'expiry_date' => '2027-05-25', 'manufacture_date' => '2026-05-25', 'origin_country' => 'Indonesia', 'cost' => 28000],
            ]],
            // PRD-0017: Desinfektan 5L
            ['sku' => 'CHEM-001', 'batches' => [
                ['batch_number' => 'B-20260501-H', 'expiry_date' => '2027-05-01', 'manufacture_date' => '2026-05-01', 'origin_country' => 'Indonesia', 'cost' => 55000],
            ]],
            // PRD-0018: Pelarut Industri A-100
            ['sku' => 'CHEM-002', 'batches' => [
                ['batch_number' => 'B-20260401-H', 'expiry_date' => '2027-04-01', 'manufacture_date' => '2026-04-01', 'origin_country' => 'Singapore', 'cost' => 87500],
            ]],
        ];

        $count = 0;
        foreach ($batchData as $item) {
            $product = $batchProducts->firstWhere('sku', $item['sku']);
            if (!$product) continue;

            foreach ($item['batches'] as $b) {
                ProductBatch::create(array_merge($b, [
                    'product_id' => $product->id,
                ]));
                $count++;
            }
        }

        $this->command->info("Product batches seeded: {$count} batches");
    }
}
