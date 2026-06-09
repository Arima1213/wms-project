<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Uom;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = \App\Models\Product::class;

    public function definition(): array
    {
        $category = Category::inRandomOrder()->first() ?? Category::factory();
        $uom = Uom::inRandomOrder()->first() ?? Uom::factory();

        return [
            'code' => 'PRD-' . strtoupper(fake()->unique()->bothify('??###')),
            'sku' => fake()->unique()->ean13(),
            'barcode' => fake()->unique()->ean13(),
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->sentence(),
            'category_id' => $category,
            'unit_id' => $uom,
            'length_cm' => fake()->randomFloat(2, 1, 100),
            'width_cm' => fake()->randomFloat(2, 1, 100),
            'height_cm' => fake()->randomFloat(2, 1, 100),
            'weight_kg' => fake()->randomFloat(2, 0.1, 50),
            'min_stock' => fake()->randomFloat(0, 1, 10),
            'max_stock' => fake()->randomFloat(0, 10, 100),
            'reorder_point' => fake()->randomFloat(0, 5, 50),
            'safety_stock' => fake()->randomFloat(0, 1, 20),
            'product_type' => fake()->randomElement(['standard', 'oversized', 'hazmat', 'cold']),
            'track_batch' => fake()->boolean(20),
            'track_expiry' => fake()->boolean(20),
            'hs_code' => fake()->numerify('####.##.##'),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => ['is_active' => false]);
    }
}
