<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Inventory;
use App\Models\Warehouse;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductLowStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_scope_low_stock_returns_products_below_min_stock(): void
    {
        $category = ProductCategory::factory()->create();

        // Product A: min_stock 10, stock 5 → LOW STOCK
        $productA = Product::factory()->create([
            'min_stock' => 10,
            'category_id' => $category->id,
        ]);

        // Product B: min_stock 10, stock 20 → OK
        $productB = Product::factory()->create([
            'min_stock' => 10,
            'category_id' => $category->id,
        ]);

        // Product C: min_stock 0 → tidak dihitung
        $productC = Product::factory()->create([
            'min_stock' => 0,
            'category_id' => $category->id,
        ]);

        $warehouse = Warehouse::factory()->create();

        Inventory::factory()->create([
            'product_id' => $productA->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 5,
            'available_quantity' => 5,
        ]);

        Inventory::factory()->create([
            'product_id' => $productB->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 20,
            'available_quantity' => 20,
        ]);

        $lowStockProducts = Product::lowStock()->get();

        $this->assertTrue($lowStockProducts->contains($productA->id));
        $this->assertFalse($lowStockProducts->contains($productB->id));
        $this->assertFalse($lowStockProducts->contains($productC->id));
    }

    public function test_scope_low_stock_ignores_min_stock_zero(): void
    {
        $category = ProductCategory::factory()->create();

        $product = Product::factory()->create([
            'min_stock' => 0,
            'category_id' => $category->id,
        ]);

        $warehouse = Warehouse::factory()->create();

        Inventory::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 0,
            'available_quantity' => 0,
        ]);

        $result = Product::lowStock()->get();
        $this->assertCount(0, $result);
    }

    public function test_scope_low_stock_no_inventory_is_low_stock(): void
    {
        $category = ProductCategory::factory()->create();

        $product = Product::factory()->create([
            'min_stock' => 5,
            'category_id' => $category->id,
        ]);

        $result = Product::lowStock()->get();
        $this->assertTrue($result->contains($product->id));
    }

    public function test_scope_low_stock_with_active_scope(): void
    {
        $category = ProductCategory::factory()->create();

        $activeProduct = Product::factory()->create([
            'min_stock' => 10,
            'is_active' => true,
            'category_id' => $category->id,
        ]);

        $inactiveProduct = Product::factory()->create([
            'min_stock' => 10,
            'is_active' => false,
            'category_id' => $category->id,
        ]);

        $warehouse = Warehouse::factory()->create();

        Inventory::factory()->create([
            'product_id' => $activeProduct->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 2,
            'available_quantity' => 2,
        ]);

        Inventory::factory()->create([
            'product_id' => $inactiveProduct->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 2,
            'available_quantity' => 2,
        ]);

        $result = Product::active()->lowStock()->get();
        $this->assertTrue($result->contains($activeProduct->id));
        $this->assertFalse($result->contains($inactiveProduct->id));
    }
}
