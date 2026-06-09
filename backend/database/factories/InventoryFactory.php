<?php

namespace Database\Factories;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryFactory extends Factory
{
    protected $model = Inventory::class;

    public function definition(): array
    {
        $product = Product::inRandomOrder()->first() ?? Product::factory()->create();
        $warehouse = Warehouse::inRandomOrder()->first() ?? Warehouse::factory()->create();

        $qty = fake()->randomFloat(2, 10, 1000);

        return [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => $qty,
            'available_quantity' => $qty,
            'reserved_quantity' => 0,
            'minimum_stock' => fake()->randomFloat(2, 1, 10),
            'maximum_stock' => fake()->randomFloat(2, 100, 2000),
            'reorder_point' => fake()->randomFloat(2, 5, 50),
            'location' => 'A-' . fake()->randomNumber(2) . '-' . fake()->randomNumber(3),
        ];
    }
}
