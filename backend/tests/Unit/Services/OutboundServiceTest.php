<?php

namespace Tests\Unit\Services;

use App\Models\Inventory;
use App\Models\Outbound;
use App\Models\OutboundItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\OutboundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutboundServiceTest extends TestCase
{
    use RefreshDatabase;

    protected OutboundService $outboundService;
    protected User $user;
    protected Warehouse $warehouse;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->outboundService = $this->app->make(OutboundService::class);
        $this->user = User::factory()->create();
        $this->warehouse = Warehouse::factory()->create();
        $this->product = Product::factory()->create();
    }

    /** @test */
    public function it_can_ship_a_pending_outbound()
    {
        // Arrange - create inventory with sufficient stock
        Inventory::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'quantity' => 500,
            'reserved_quantity' => 0,
            'available_quantity' => 500,
        ]);

        $outbound = Outbound::factory()
            ->has(OutboundItem::factory()->state([
                'product_id' => $this->product->id,
                'ordered_qty' => 100,
            ]), 'items')
            ->create([
                'warehouse_id' => $this->warehouse->id,
                'created_by' => $this->user->id,
                'status' => 'pending',
            ]);

        // Act
        $result = $this->outboundService->ship($outbound, $this->user->id);

        // Assert
        $this->assertEquals('shipped', $result->status);
        $this->assertNotNull($result->shipped_date);

        $item = $result->items->first();
        $this->assertEquals($item->ordered_qty, $item->picked_qty);
        $this->assertEquals($item->ordered_qty, $item->shipped_qty);
        $this->assertEquals('shipped', $item->status);

        // Assert inventory decreased
        $inventory = Inventory::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();
        $this->assertEquals(400, $inventory->quantity);
        $this->assertEquals(400, $inventory->available_quantity);
    }

    /** @test */
    public function it_throws_exception_when_shipping_outbound_with_invalid_status()
    {
        // Arrange
        $outbound = Outbound::factory()
            ->has(OutboundItem::factory()->count(1), 'items')
            ->create([
                'warehouse_id' => $this->warehouse->id,
                'created_by' => $this->user->id,
                'status' => 'shipped',
                'shipped_date' => now(),
            ]);

        // Assert & Act
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot ship this outbound. Invalid status.');

        $this->outboundService->ship($outbound, $this->user->id);
    }

    /** @test */
    public function it_throws_exception_when_insufficient_stock_for_shipping()
    {
        // Arrange - no inventory created, so stock is 0
        $outbound = Outbound::factory()
            ->has(OutboundItem::factory()->state([
                'product_id' => $this->product->id,
                'ordered_qty' => 100,
            ]), 'items')
            ->create([
                'warehouse_id' => $this->warehouse->id,
                'created_by' => $this->user->id,
                'status' => 'pending',
            ]);

        // Assert & Act
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient stock');

        $this->outboundService->ship($outbound, $this->user->id);
    }
}
