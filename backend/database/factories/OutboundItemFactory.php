<?php

namespace Database\Factories;

use App\Models\OutboundItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class OutboundItemFactory extends Factory
{
    protected $model = OutboundItem::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'ordered_qty' => fake()->randomFloat(4, 1, 100),
            'picked_qty' => 0,
            'shipped_qty' => 0,
            'unit_price' => fake()->randomFloat(4, 5000, 500000),
            'status' => 'pending',
        ];
    }

    public function picked(): static
    {
        return $this->state(fn(array $attributes) => [
            'picked_qty' => function (array $attrs) {
                return $attrs['ordered_qty'] ?? 1;
            },
            'status' => 'picked',
        ]);
    }

    public function shipped(): static
    {
        return $this->state(fn(array $attributes) => [
            'picked_qty' => function (array $attrs) {
                return $attrs['ordered_qty'] ?? 1;
            },
            'shipped_qty' => function (array $attrs) {
                return $attrs['ordered_qty'] ?? 1;
            },
            'status' => 'shipped',
        ]);
    }
}
