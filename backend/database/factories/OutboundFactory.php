<?php

namespace Database\Factories;

use App\Models\Outbound;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class OutboundFactory extends Factory
{
    protected $model = Outbound::class;

    public function definition(): array
    {
        return [
            'outbound_number' => strtoupper(fake()->unique()->bothify('OUT-####-????')),
            'warehouse_id' => Warehouse::factory(),
            'type' => fake()->randomElement(['sales', 'transfer', 'return_supplier', 'other']),
            'status' => 'pending',
            'order_date' => fake()->dateTimeBetween('-1 week', 'now'),
            'destination_name' => fake()->company(),
            'destination_address' => fake()->address(),
            'shipping_method' => fake()->randomElement(['JNE', 'TIKI', 'SiCepat', 'Grab', 'GoSend']),
            'notes' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
        ];
    }

    public function withItems(int $count = 2): static
    {
        return $this->has(
            \App\Models\OutboundItem::factory()->count($count),
            'items'
        );
    }

    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function picking(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'picking',
        ]);
    }

    public function shipped(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'shipped',
            'shipped_date' => now(),
        ]);
    }
}
