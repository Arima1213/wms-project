<?php

namespace Database\Factories;

use App\Models\Uom;
use Illuminate\Database\Eloquent\Factories\Factory;

class UomFactory extends Factory
{
    protected $model = Uom::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('UOM-??'),
            'name' => fake()->unique()->word(),
            'symbol' => fake()->unique()->lexify('???'),
            'type' => fake()->randomElement(['unit', 'weight', 'volume', 'length']),
            'conversion_factor' => 1.0000,
            'is_active' => true,
        ];
    }

    public function unit(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'unit',
            'conversion_factor' => 1.0000,
        ]);
    }

    public function weight(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'weight',
        ]);
    }
}
