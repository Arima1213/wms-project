<?php

namespace Tests\Feature\Api\V1;

use App\Models\Product;
use App\Models\StockOpname;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class StockOpnameApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        config(['scout.driver' => null]);

        foreach (['view stock_opnames', 'create stock_opnames', 'edit stock_opnames', 'approve stock_opnames'] as $perm) {
            Permission::create(['name' => $perm, 'guard_name' => 'sanctum']);
        }

        $this->user = User::factory()->create();
        $this->user->givePermissionTo([
            'view stock_opnames',
            'create stock_opnames',
            'edit stock_opnames',
            'approve stock_opnames',
        ]);
    }

    /** @test */
    public function can_create_stock_opname()
    {
        $warehouse = Warehouse::factory()->create();

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/stock-opnames', [
                'warehouse_id' => $warehouse->id,
                'notes' => 'Test stock opname creation',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'opname_number', 'warehouse_id', 'status', 'notes'],
            ])
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.notes', 'Test stock opname creation')
            ->assertJsonPath('data.warehouse_id', $warehouse->id);

        $this->assertDatabaseHas('stock_opnames', [
            'warehouse_id' => $warehouse->id,
            'status' => 'draft',
            'notes' => 'Test stock opname creation',
        ]);
    }

    /** @test */
    public function can_start_stock_opname()
    {
        $warehouse = Warehouse::factory()->create();
        $opname = StockOpname::create([
            'opname_number' => 'SO-TEST-START-001',
            'warehouse_id' => $warehouse->id,
            'created_by' => $this->user->id,
            'status' => 'draft',
            'type' => 'full',
            'start_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/stock-opnames/{$opname->id}/start");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'in_progress');

        $this->assertDatabaseHas('stock_opnames', [
            'id' => $opname->id,
            'status' => 'in_progress',
        ]);
    }

    /** @test */
    public function can_submit_stock_opname()
    {
        $warehouse = Warehouse::factory()->create();
        $opname = StockOpname::create([
            'opname_number' => 'SO-TEST-SUBMIT-001',
            'warehouse_id' => $warehouse->id,
            'created_by' => $this->user->id,
            'status' => 'in_progress',
            'type' => 'full',
            'start_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/stock-opnames/{$opname->id}/submit");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'submitted');

        $this->assertDatabaseHas('stock_opnames', [
            'id' => $opname->id,
            'status' => 'submitted',
        ]);
    }

    /** @test */
    public function can_cancel_stock_opname()
    {
        $warehouse = Warehouse::factory()->create();
        $opname = StockOpname::create([
            'opname_number' => 'SO-TEST-CANCEL-001',
            'warehouse_id' => $warehouse->id,
            'created_by' => $this->user->id,
            'status' => 'draft',
            'type' => 'full',
            'start_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/stock-opnames/{$opname->id}/cancel");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('stock_opnames', [
            'id' => $opname->id,
            'status' => 'cancelled',
        ]);
    }

    /** @test */
    public function can_update_with_upsert_items()
    {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();

        $opname = StockOpname::create([
            'opname_number' => 'SO-TEST-UPSERT-001',
            'warehouse_id' => $warehouse->id,
            'created_by' => $this->user->id,
            'status' => 'in_progress',
            'type' => 'full',
            'start_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/stock-opnames/{$opname->id}", [
                'notes' => 'Updated with item counts',
                'items' => [
                    [
                        'product_id' => $product->id,
                        'system_qty' => 100,
                        'actual_qty' => 95,
                        'difference_qty' => -5,
                    ],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.notes', 'Updated with item counts')
            ->assertJsonStructure([
                'data' => [
                    'id', 'opname_number', 'notes', 'items' => [
                        '*' => ['id', 'product_id', 'system_qty', 'counted_qty', 'variance'],
                    ],
                ],
            ]);

        $this->assertDatabaseHas('stock_opname_items', [
            'stock_opname_id' => $opname->id,
            'product_id' => $product->id,
            'system_qty' => '100.0000',
            'counted_qty' => '95.0000',
            'variance' => '-5.0000',
            'variance_status' => 'short',
        ]);

        // Second update: upsert same product+slot (null) with new values
        $response2 = $this->actingAs($this->user)
            ->putJson("/api/v1/stock-opnames/{$opname->id}", [
                'items' => [
                    [
                        'product_id' => $product->id,
                        'system_qty' => 100,
                        'actual_qty' => 100,
                        'difference_qty' => 0,
                    ],
                ],
            ]);

        $response2->assertStatus(200);

        // Must have exactly one item (upserted, not duplicated)
        $this->assertDatabaseCount('stock_opname_items', 1);
        $this->assertDatabaseHas('stock_opname_items', [
            'stock_opname_id' => $opname->id,
            'product_id' => $product->id,
            'counted_qty' => '100.0000',
            'variance' => '0.0000',
            'variance_status' => 'match',
        ]);
    }

    /** @test */
    public function staff_cannot_start_stock_opname()
    {
        $staffUser = User::factory()->create();

        $warehouse = Warehouse::factory()->create();
        $opname = StockOpname::create([
            'opname_number' => 'SO-TEST-403-START',
            'warehouse_id' => $warehouse->id,
            'created_by' => $this->user->id,
            'status' => 'draft',
            'type' => 'full',
            'start_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($staffUser)
            ->postJson("/api/v1/stock-opnames/{$opname->id}/start");

        $response->assertStatus(403);
    }

    /** @test */
    public function staff_cannot_submit_stock_opname()
    {
        $staffUser = User::factory()->create();

        $warehouse = Warehouse::factory()->create();
        $opname = StockOpname::create([
            'opname_number' => 'SO-TEST-403-SUBMIT',
            'warehouse_id' => $warehouse->id,
            'created_by' => $this->user->id,
            'status' => 'in_progress',
            'type' => 'full',
            'start_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($staffUser)
            ->postJson("/api/v1/stock-opnames/{$opname->id}/submit");

        $response->assertStatus(403);
    }

    /** @test */
    public function can_list_stock_opnames()
    {
        $warehouse = Warehouse::factory()->create();
        StockOpname::create([
            'opname_number' => 'SO-TEST-LIST-001',
            'warehouse_id' => $warehouse->id,
            'created_by' => $this->user->id,
            'status' => 'draft',
            'type' => 'full',
            'start_date' => now()->toDateString(),
        ]);
        StockOpname::create([
            'opname_number' => 'SO-TEST-LIST-002',
            'warehouse_id' => $warehouse->id,
            'created_by' => $this->user->id,
            'status' => 'in_progress',
            'type' => 'full',
            'start_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/stock-opnames');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'opname_number', 'warehouse_id', 'status', 'type', 'start_date', 'notes'],
                ],
            ]);
    }

    /** @test */
    public function can_view_stock_opname_detail()
    {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();

        $opname = StockOpname::create([
            'opname_number' => 'SO-TEST-DETAIL-001',
            'warehouse_id' => $warehouse->id,
            'created_by' => $this->user->id,
            'status' => 'in_progress',
            'type' => 'full',
            'start_date' => now()->toDateString(),
        ]);

        $opname->items()->create([
            'product_id' => $product->id,
            'system_qty' => 50,
            'counted_qty' => 48,
            'variance' => -2,
            'variance_status' => 'short',
            'counted_by' => $this->user->id,
            'counted_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/stock-opnames/{$opname->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $opname->id)
            ->assertJsonPath('data.opname_number', 'SO-TEST-DETAIL-001')
            ->assertJsonPath('data.status', 'in_progress')
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'opname_number',
                    'warehouse_id',
                    'status',
                    'items' => [
                        '*' => ['id', 'product_id', 'system_qty', 'counted_qty', 'variance', 'variance_status'],
                    ],
                ],
            ]);
    }
}
