<?php

namespace Database\Factories;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    public function definition(): array
    {
        return [
            'code' => 'WH-' . strtoupper(fake()->unique()->bothify('??##')),
            'name' => fake()->company() . ' Warehouse',
            'address' => fake()->address(),
            'city' => fake()->city(),
            'province' => fake()->state(),
            'country' => 'Indonesia',
            'postal_code' => fake()->postcode(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'latitude' => fake()->latitude(-8, -6),
            'longitude' => fake()->longitude(106, 115),
            'description' => fake()->sentence(),
            'is_active' => true,
            'metadata' => null,
            'created_by' => 1,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => ['is_active' => false]);
    }
}
