<?php

namespace Tests\Unit\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InventoryService $inventoryService;
    protected User $user;
    protected Warehouse $warehouse;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventoryService = $this->app->make(InventoryService::class);
        $this->user = User::factory()->create();
        $this->warehouse = Warehouse::factory()->create();
        $this->product = Product::factory()->create();
    }

    /** @test */
    public function it_can_receive_stock_and_record_gr_transaction()
    {
        // Act
        $inventory = $this->inventoryService->receiveStock(
            $this->product->id,
            $this->warehouse->id,
            100.0,
            $this->user->id,
            [
                'reference_type' => 'App\\Models\\Inbound',
                'reference_id' => 1,
                'reference_number' => 'INB-001',
                'notes' => 'Test receive',
            ]
        );

        // Assert inventory
        $this->assertNotNull($inventory);
        $this->assertEquals(100.0, $inventory->quantity);
        $this->assertEquals(100.0, $inventory->available_quantity);
        $this->assertEquals($this->product->id, $inventory->product_id);
        $this->assertEquals($this->warehouse->id, $inventory->warehouse_id);

        // Assert transaction
        $transaction = StockTransaction::where('transaction_type', 'GR')->first();
        $this->assertNotNull($transaction);
        $this->assertEquals(100.0, $transaction->quantity);
        $this->assertEquals('Test receive', $transaction->notes);
        $this->assertEquals($this->product->id, $transaction->product_id);
    }

    /** @test */
    public function it_can_issue_stock_and_record_gi_transaction()
    {
        // Arrange - first receive stock
        $this->inventoryService->receiveStock(
            $this->product->id,
            $this->warehouse->id,
            200.0,
            $this->user->id
        );

        // Act
        $inventory = $this->inventoryService->issueStock(
            $this->product->id,
            $this->warehouse->id,
            50.0,
            $this->user->id,
            [
                'reference_type' => 'App\\Models\\Outbound',
                'reference_id' => 1,
                'reference_number' => 'OUT-001',
                'notes' => 'Test issue',
            ]
        );

        // Assert inventory decreased
        $this->assertNotNull($inventory);
        $this->assertEquals(150.0, $inventory->quantity);
        $this->assertEquals(150.0, $inventory->available_quantity);

        // Assert transaction
        $transaction = StockTransaction::where('transaction_type', 'GI')->first();
        $this->assertNotNull($transaction);
        $this->assertEquals(50.0, $transaction->quantity);
        $this->assertEquals('Test issue', $transaction->notes);
    }

    /** @test */
    public function it_throws_exception_when_issuing_more_stock_than_available()
    {
        // Arrange - no stock yet
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient stock');

        // Act
        $this->inventoryService->issueStock(
            $this->product->id,
            $this->warehouse->id,
            100.0,
            $this->user->id
        );
    }

    /** @test */
    public function it_can_transfer_stock_between_warehouses()
    {
        // Arrange
        $destWarehouse = Warehouse::factory()->create();

        // Receive stock in source warehouse
        $this->inventoryService->receiveStock(
            $this->product->id,
            $this->warehouse->id,
            300.0,
            $this->user->id
        );

        // Act
        $this->inventoryService->transferStock(
            $this->product->id,
            $this->warehouse->id,
            $destWarehouse->id,
            100.0,
            $this->user->id,
            ['notes' => 'Test transfer']
        );

        // Assert source decreased
        $sourceInventory = Inventory::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();
        $this->assertEquals(200.0, $sourceInventory->quantity);

        // Assert destination increased
        $destInventory = Inventory::where('product_id', $this->product->id)
            ->where('warehouse_id', $destWarehouse->id)
            ->first();
        $this->assertNotNull($destInventory);
        $this->assertEquals(100.0, $destInventory->quantity);

        // Assert TR transaction recorded
        $transaction = StockTransaction::where('transaction_type', 'TR')->first();
        $this->assertNotNull($transaction);
        $this->assertEquals(100.0, $transaction->quantity);
        $this->assertEquals($this->warehouse->id, $transaction->source_warehouse_id);
        $this->assertEquals($destWarehouse->id, $transaction->dest_warehouse_id);
    }

    /** @test */
    public function it_can_adjust_stock_positive_and_record_adj_plus()
    {
        // Arrange
        $this->inventoryService->receiveStock(
            $this->product->id,
            $this->warehouse->id,
            100.0,
            $this->user->id
        );

        // Act
        $this->inventoryService->adjustStock(
            $this->product->id,
            $this->warehouse->id,
            50.0, // positive = increase
            $this->user->id,
            ['notes' => 'Stock opname adjustment +']
        );

        // Assert
        $inventory = Inventory::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();
        $this->assertEquals(150.0, $inventory->quantity);
        $this->assertEquals(150.0, $inventory->available_quantity);

        $transaction = StockTransaction::where('transaction_type', 'ADJ+')->first();
        $this->assertNotNull($transaction);
        $this->assertEquals(50.0, $transaction->quantity);
    }

    /** @test */
    public function it_throws_insufficient_stock_exception_when_adjustment_goes_negative()
    {
        // Arrange - stock is 100
        $this->inventoryService->receiveStock(
            $this->product->id,
            $this->warehouse->id,
            100.0,
            $this->user->id
        );

        // Assert & Act
        $this->expectException(InsufficientStockException::class);

        // Try to adjust -150 (would go to -50)
        $this->inventoryService->adjustStock(
            $this->product->id,
            $this->warehouse->id,
            -150.0,
            $this->user->id
        );
    }

    /** @test */
    public function it_can_create_and_update_inventory()
    {
        // Act - create
        $inventory = $this->inventoryService->create([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'quantity' => 50,
            'reserved_quantity' => 0,
            'available_quantity' => 50,
        ]);

        $this->assertNotNull($inventory->id);
        $this->assertEquals(50, $inventory->quantity);

        // Act - update
        $updated = $this->inventoryService->update($inventory->id, [
            'quantity' => 75,
            'available_quantity' => 75,
        ]);

        $this->assertEquals(75, $updated->quantity);
    }
}
