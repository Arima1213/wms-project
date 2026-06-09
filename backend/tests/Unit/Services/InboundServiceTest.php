<?php

namespace Tests\Unit\Services;

use App\Models\Inbound;
use App\Models\InboundItem;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InboundService;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InboundServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InboundService $inboundService;
    protected User $user;
    protected Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inboundService = $this->app->make(InboundService::class);
        $this->user = User::factory()->create();
        $this->warehouse = Warehouse::factory()->create();
    }

    /** @test */
    public function it_can_full_receive_an_inbound()
    {
        // Arrange
        $inbound = Inbound::factory()
            ->has(InboundItem::factory()->count(2), 'items')
            ->create([
                'warehouse_id' => $this->warehouse->id,
                'created_by' => $this->user->id,
                'status' => 'pending',
            ]);

        // Act
        $result = $this->inboundService->receive($inbound, $this->user->id);

        // Assert
        $this->assertEquals('received', $result->status);
        $this->assertNotNull($result->received_date);

        foreach ($result->items as $item) {
            $this->assertEquals($item->expected_qty, $item->received_qty);
            $this->assertNotNull($item->received_at);
        }

        // Assert inventory was updated
        foreach ($inbound->items as $item) {
            $inventory = Inventory::where('product_id', $item->product_id)
                ->where('warehouse_id', $this->warehouse->id)
                ->first();
            $this->assertNotNull($inventory);
            $this->assertEquals($item->expected_qty, $inventory->quantity);
        }
    }

    /** @test */
    public function it_can_partial_receive_an_inbound()
    {
        // Arrange
        $inbound = Inbound::factory()
            ->has(InboundItem::factory()->count(2), 'items')
            ->create([
                'warehouse_id' => $this->warehouse->id,
                'created_by' => $this->user->id,
                'status' => 'pending',
            ]);

        $items = $inbound->items;
        $partialItems = [
            ['id' => $items[0]->id, 'received_qty' => $items[0]->expected_qty / 2],
            ['id' => $items[1]->id, 'received_qty' => $items[1]->expected_qty],
        ];

        // Act
        $result = $this->inboundService->receive($inbound, $this->user->id, $partialItems);

        // Assert
        $this->assertEquals('partial', $result->status);
        $this->assertEquals($items[0]->expected_qty / 2, $result->items[0]->received_qty);
        $this->assertEquals($items[1]->expected_qty, $result->items[1]->received_qty);
    }

    /** @test */
    public function it_throws_exception_when_receiving_already_received_inbound()
    {
        // Arrange
        $inbound = Inbound::factory()
            ->has(InboundItem::factory()->count(1), 'items')
            ->create([
                'warehouse_id' => $this->warehouse->id,
                'created_by' => $this->user->id,
                'status' => 'received',
                'received_date' => now(),
            ]);

        // Assert & Act
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Inbound already received or cancelled.');

        $this->inboundService->receive($inbound, $this->user->id);
    }

    /** @test */
    public function it_throws_exception_when_received_qty_exceeds_expected_qty()
    {
        // Arrange
        $inbound = Inbound::factory()
            ->has(InboundItem::factory()->count(1), 'items')
            ->create([
                'warehouse_id' => $this->warehouse->id,
                'created_by' => $this->user->id,
                'status' => 'pending',
            ]);

        $items = $inbound->items;
        $exceedItems = [
            ['id' => $items[0]->id, 'received_qty' => $items[0]->expected_qty + 100],
        ];

        // Assert & Act
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Received quantity cannot exceed expected quantity');

        $this->inboundService->receive($inbound, $this->user->id, $exceedItems);
    }
}
