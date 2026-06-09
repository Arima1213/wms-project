<?php

namespace Database\Factories;

use App\Models\Inbound;
use App\Models\InboundItem;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class InboundFactory extends Factory
{
    protected $model = Inbound::class;

    public function definition(): array
    {
        $warehouse = Warehouse::inRandomOrder()->first() ?? Warehouse::factory();

        return [
            'inbound_number' => 'INB-' . date('Ymd') . '-' . fake()->unique()->numerify('######'),
            'warehouse_id' => $warehouse->id,
            'reference_type' => fake()->randomElement(['purchase_order', 'transfer', 'return', 'manual']),
            'reference_number' => 'PO-' . fake()->unique()->numerify('########'),
            'status' => 'pending',
            'notes' => fake()->sentence(),
            'created_by' => 1,
        ];
    }

    public function withItems(int $count = 3): static
    {
        return $this->afterCreating(function (Inbound $inbound) use ($count) {
            for ($i = 0; $i < $count; $i++) {
                $product = Product::inRandomOrder()->first() ?? Product::factory()->create();
                $expectedQty = fake()->randomFloat(2, 1, 100);

                InboundItem::factory()->create([
                    'inbound_id' => $inbound->id,
                    'product_id' => $product->id,
                    'expected_qty' => $expectedQty,
                    'received_qty' => null,
                ]);
            }
        });
    }

    public function received(): static
    {
        return $this->state(fn(array $attributes) => ['status' => 'received']);
    }

    public function partial(): static
    {
        return $this->state(fn(array $attributes) => ['status' => 'partial']);
    }

    public function cancelled(): static
    {
        return $this->state(fn(array $attributes) => ['status' => 'cancelled']);
    }
}
