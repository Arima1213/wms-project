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
            'rack_slot_id' => null,
            'batch_number' => fake()->optional(0.3)->bothify('BATCH-####'),
            'expiry_date' => fake()->optional(0.3)->dateTimeBetween('+1 month', '+2 years'),
            'quantity' => $qty,
            'available_quantity' => $qty,
            'reserved_quantity' => 0,
            'unit_cost' => fake()->randomFloat(2, 1000, 500000),
        ];
    }

    public function withLowStock(): static
    {
        return $this->state(fn(array $attributes) => [
            'quantity' => 2,
            'available_quantity' => 2,
        ]);
    }
}
