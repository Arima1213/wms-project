<?php

namespace Database\Factories;

use App\Models\Inbound;
use App\Models\InboundItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class InboundItemFactory extends Factory
{
    protected $model = InboundItem::class;

    public function definition(): array
    {
        $product = Product::inRandomOrder()->first() ?? Product::factory()->create();

        return [
            'inbound_id' => Inbound::factory(),
            'product_id' => $product->id,
            'expected_qty' => fake()->randomFloat(2, 1, 100),
            'received_qty' => null,
            'notes' => fake()->sentence(),
        ];
    }

    public function received(): static
    {
        return $this->state(function (array $attributes) {
            return ['received_qty' => $attributes['expected_qty']];
        });
    }

    public function partial(float $receivedQty = null): static
    {
        return $this->state(function (array $attributes) use ($receivedQty) {
            return ['received_qty' => $receivedQty ?? fake()->randomFloat(2, 1, $attributes['expected_qty'] - 0.01)];
        });
    }
}
