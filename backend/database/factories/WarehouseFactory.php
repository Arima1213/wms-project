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
            'postal_code' => fake()->postcode(),
            'latitude' => fake()->latitude(-8, -6),
            'longitude' => fake()->longitude(106, 115),
            'capacity_m2' => fake()->randomFloat(2, 100, 10000),
            'pic_name' => fake()->name(),
            'pic_phone' => fake()->phoneNumber(),
            'pic_email' => fake()->companyEmail(),
            'warehouse_type' => fake()->randomElement(['reguler', 'cold_storage', 'bonded', 'konsinyasi']),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => ['is_active' => false]);
    }

    public function coldStorage(): static
    {
        return $this->state(fn(array $attributes) => ['warehouse_type' => 'cold_storage']);
    }
}
