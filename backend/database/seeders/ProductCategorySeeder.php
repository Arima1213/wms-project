<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        ProductCategory::create(['code' => 'ELEC', 'name' => 'Elektronik', 'description' => 'Produk elektronik dan aksesoris']);
        ProductCategory::create(['code' => 'PACK', 'name' => 'Packaging', 'description' => 'Bahan dan alat packaging']);
        ProductCategory::create(['code' => 'RAWM', 'name' => 'Bahan Baku', 'description' => 'Bahan baku industri']);
        ProductCategory::create(['code' => 'FMCG', 'name' => 'Fast Moving Consumer Goods', 'description' => 'Produk konsumen cepat saji']);
        ProductCategory::create(['code' => 'FOOD', 'name' => 'Makanan & Minuman', 'description' => 'Produk makanan dan minuman']);
        ProductCategory::create(['code' => 'CHEM', 'name' => 'Kimia', 'description' => 'Produk kimia dan bahan berbahaya']);

        $this->command->info('Product categories seeded: ' . ProductCategory::count() . ' categories');
    }
}
