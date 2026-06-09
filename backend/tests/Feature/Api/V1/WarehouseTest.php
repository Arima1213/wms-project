<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class WarehouseTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Bypass permission checks for form requests and gate
        Gate::define('warehouse.create', fn() => true);
        Gate::define('warehouse.update', fn() => true);
        Gate::define('warehouse.delete', fn() => true);
    }

    /** @test */
    public function can_list_warehouses()
    {
        Warehouse::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/warehouses');

        $response->assertStatus(200);
        // Paginated response from apiResource
        $response->assertJsonCount(3, 'data');
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'code', 'name', 'is_active'],
            ],
        ]);
    }

    /** @test */
    public function can_create_a_warehouse()
    {
        $payload = [
            'code' => 'WH-TEST',
            'name' => 'Test Warehouse',
            'address' => 'Jl. Testing No. 123',
            'city' => 'Jakarta',
            'province' => 'DKI Jakarta',
            'postal_code' => '12345',
            'warehouse_type' => 'reguler',
            'pic_name' => 'John Doe',
            'pic_phone' => '081234567890',
            'pic_email' => 'john@example.com',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/warehouses', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.code', 'WH-TEST')
            ->assertJsonPath('data.name', 'Test Warehouse');

        $this->assertDatabaseHas('warehouses', [
            'code' => 'WH-TEST',
            'name' => 'Test Warehouse',
        ]);
    }

    /** @test */
    public function can_show_a_warehouse()
    {
        $warehouse = Warehouse::factory()->create([
            'code' => 'WH-SHOW',
            'name' => 'Show Warehouse',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/warehouses/{$warehouse->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $warehouse->id)
            ->assertJsonPath('data.code', 'WH-SHOW')
            ->assertJsonPath('data.name', 'Show Warehouse');
    }

    /** @test */
    public function can_update_a_warehouse()
    {
        $warehouse = Warehouse::factory()->create([
            'code' => 'WH-ORIGINAL',
            'name' => 'Original Name',
        ]);

        $payload = [
            'name' => 'Updated Warehouse Name',
            'city' => 'Bandung',
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/warehouses/{$warehouse->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Warehouse Name')
            ->assertJsonPath('data.city', 'Bandung');

        $this->assertDatabaseHas('warehouses', [
            'id' => $warehouse->id,
            'name' => 'Updated Warehouse Name',
        ]);
    }

    /** @test */
    public function can_delete_a_warehouse()
    {
        $warehouse = Warehouse::factory()->create();

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/warehouses/{$warehouse->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Warehouse deleted']);

        // Soft delete
        $this->assertSoftDeleted('warehouses', [
            'id' => $warehouse->id,
        ]);
    }

    /** @test */
    public function validation_fails_when_creating_without_required_fields()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/warehouses', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code', 'name']);
    }

    /** @test */
    public function validation_fails_when_creating_with_duplicate_code()
    {
        Warehouse::factory()->create(['code' => 'WH-DUP']);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/warehouses', [
                'code' => 'WH-DUP',
                'name' => 'Duplicate Code Warehouse',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_warehouse_endpoints()
    {
        $response = $this->getJson('/api/v1/warehouses');
        $response->assertStatus(401);

        $response = $this->postJson('/api/v1/warehouses', []);
        $response->assertStatus(401);

        $response = $this->getJson('/api/v1/warehouses/1');
        $response->assertStatus(401);

        $response = $this->putJson('/api/v1/warehouses/1', []);
        $response->assertStatus(401);

        $response = $this->deleteJson('/api/v1/warehouses/1');
        $response->assertStatus(401);
    }
}
