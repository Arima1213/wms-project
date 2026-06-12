<?php

namespace Tests\Unit;

use App\Exceptions\InsufficientStockException;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\StockTransaction;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\DocumentSequenceService;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected InventoryService $inventoryService;
    protected DocumentSequenceService $documentService;
    protected User $user;
    protected Warehouse $warehouse;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventoryService = $this->app->make(InventoryService::class);
        $this->documentService = $this->app->make(DocumentSequenceService::class);
        $this->user = User::factory()->create();
        $this->warehouse = Warehouse::factory()->create();
        $this->product = Product::factory()->create();
    }

    /** @test */
    public function rollback_on_issue_stock_crash_does_not_partially_save()
    {
        // Arrange: receive 100 stock
        $this->inventoryService->receiveStock(
            $this->product->id,
            $this->warehouse->id,
            100.0,
            $this->user->id
        );

        // Act: try to issue more than available (causes InsufficientStockException
        // inside the service's DB::transaction, which triggers a full rollback)
        try {
            $this->inventoryService->issueStock(
                $this->product->id,
                $this->warehouse->id,
                200.0, // more than available — will throw
                $this->user->id
            );
            $this->fail('Expected InsufficientStockException was not thrown.');
        } catch (InsufficientStockException $e) {
            // Expected — the inner transaction was rolled back
        }

        // Assert: inventory quantity is fully unchanged (no partial save)
        $inventory = Inventory::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();
        $this->assertNotNull($inventory);
        $this->assertEquals(100.0, $inventory->quantity);
        $this->assertEquals(100.0, $inventory->available_quantity);

        // Assert: no GI transaction was persisted (rollback confirmed)
        $giTransactions = StockTransaction::where('transaction_type', 'GI')->count();
        $this->assertEquals(0, $giTransactions, 'No GI transaction should exist after a rolled-back issueStock call.');
    }

    /** @test */
    public function rollback_on_transfer_stock_crash_does_not_partially_save()
    {
        // Arrange: receive 100 stock in source warehouse
        $this->inventoryService->receiveStock(
            $this->product->id,
            $this->warehouse->id,
            100.0,
            $this->user->id
        );

        $destWarehouse = Warehouse::factory()->create();

        // Act: try to transfer more than available — exception inside transaction
        try {
            $this->inventoryService->transferStock(
                $this->product->id,
                $this->warehouse->id,
                $destWarehouse->id,
                200.0, // more than available — will throw
                $this->user->id
            );
            $this->fail('Expected Exception was not thrown.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('Insufficient stock', $e->getMessage());
        }

        // Assert: source inventory is unchanged
        $sourceInventory = Inventory::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();
        $this->assertNotNull($sourceInventory);
        $this->assertEquals(100.0, $sourceInventory->quantity);

        // Assert: destination inventory was NOT created (no partial transfer)
        $destInventory = Inventory::where('product_id', $this->product->id)
            ->where('warehouse_id', $destWarehouse->id)
            ->first();
        $this->assertNull($destInventory, 'Destination inventory should not exist after a rolled-back transfer.');

        // Assert: no TR transaction was recorded
        $trTransactions = StockTransaction::where('transaction_type', 'TR')->count();
        $this->assertEquals(0, $trTransactions, 'No TR transaction should exist after a rolled-back transferStock call.');
    }

    /** @test */
    public function rollback_on_adjust_stock_crash_does_not_partially_save()
    {
        // Arrange: receive 100 stock
        $this->inventoryService->receiveStock(
            $this->product->id,
            $this->warehouse->id,
            100.0,
            $this->user->id
        );

        // Act: try to adjust downwards by more than available (would go negative)
        try {
            $this->inventoryService->adjustStock(
                $this->product->id,
                $this->warehouse->id,
                -200.0, // would result in -100 — will throw
                $this->user->id
            );
            $this->fail('Expected InsufficientStockException was not thrown.');
        } catch (InsufficientStockException $e) {
            // Expected — inner transaction rolled back
        }

        // Assert: inventory is completely unchanged
        $inventory = Inventory::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();
        $this->assertNotNull($inventory);
        $this->assertEquals(100.0, $inventory->quantity);
        $this->assertEquals(100.0, $inventory->available_quantity);

        // Assert: no adjustment transaction was recorded
        $adjTransactions = StockTransaction::whereIn('transaction_type', ['ADJ+', 'ADJ-'])->count();
        $this->assertEquals(0, $adjTransactions, 'No ADJ transaction should exist after a rolled-back adjustStock call.');
    }

    /** @test */
    public function concurrent_transfer_with_pessimistic_write_prevents_double_allocation()
    {
        // This test verifies that the pessimistic row locking (lockForUpdate)
        // used inside InventoryService::transferStock serialises concurrent
        // transfers and prevents double-allocation of the same stock.
        //
        // SQLite :memory: has a single connection, so we simulate the effect
        // by executing transfers sequentially and verifying that the second
        // transfer correctly sees the updated state from the first.

        // Arrange: receive 100 stock in source warehouse
        $this->inventoryService->receiveStock(
            $this->product->id,
            $this->warehouse->id,
            100.0,
            $this->user->id
        );

        $dest1 = Warehouse::factory()->create(['code' => 'WH-DEST-A']);
        $dest2 = Warehouse::factory()->create(['code' => 'WH-DEST-B']);

        // Act 1: first transfer consumes 60 of the 100 available
        $this->inventoryService->transferStock(
            $this->product->id,
            $this->warehouse->id,
            $dest1->id,
            60.0,
            $this->user->id,
            ['notes' => 'First concurrent transfer']
        );

        // Assert: source now has 40 remaining
        $sourceInventory = Inventory::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();
        $this->assertEquals(40.0, $sourceInventory->quantity);
        $this->assertEquals(40.0, $sourceInventory->available_quantity);

        // Act 2: second transfer tries to take 50, but only 40 remains
        // The lockForUpdate inside transferStock ensures the second operation
        // sees the most recent committed state and throws an exception.
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient stock');

        $this->inventoryService->transferStock(
            $this->product->id,
            $this->warehouse->id,
            $dest2->id,
            50.0,
            $this->user->id,
            ['notes' => 'Second concurrent transfer — should fail']
        );
    }

    /** @test */
    public function stock_opname_upsert_does_not_lose_existing_items_on_re_update()
    {
        // Arrange: create a stock opname (inline since no factory exists)
        $stockOpname = StockOpname::create([
            'warehouse_id' => $this->warehouse->id,
            'opname_number' => 'SO-20260611-000001',
            'type' => 'full',
            'status' => 'in_progress',
            'start_date' => today(),
            'created_by' => $this->user->id,
        ]);

        // Create initial items using updateOrCreate (the standard upsert pattern)
        StockOpnameItem::updateOrCreate(
            [
                'stock_opname_id' => $stockOpname->id,
                'product_id' => $this->product->id,
            ],
            [
                'system_qty' => 100.0,
                'counted_qty' => 95.0,
                'variance' => -5.0,
                'variance_status' => 'short',
            ]
        );

        // Verify one item was created
        $this->assertEquals(1, $stockOpname->items()->count());

        // Act: re-update the same item (simulates re-submitting stock opname results)
        StockOpnameItem::updateOrCreate(
            [
                'stock_opname_id' => $stockOpname->id,
                'product_id' => $this->product->id,
            ],
            [
                'system_qty' => 100.0,
                'counted_qty' => 98.0,  // updated count
                'variance' => -2.0,     // recalculated variance
                'variance_status' => 'short',
            ]
        );

        // Assert: still exactly 1 item (not duplicated), with updated values
        $this->assertEquals(1, $stockOpname->items()->count(),
            'Re-updating the same StockOpnameItem must not create a duplicate row.');

        $item = $stockOpname->items()->first();
        $this->assertEquals(98.0, (float) $item->counted_qty,
            'The counted_qty should reflect the latest update.');
        $this->assertEquals(-2.0, (float) $item->variance,
            'The variance should reflect the latest update.');
        $this->assertEquals('short', $item->variance_status);

        // Assert: no other orphan items were created
        $this->assertEquals(1, StockOpnameItem::count(),
            'Total StockOpnameItem count must remain exactly 1 after a re-update.');
    }

    /** @test */
    public function document_sequence_unique_constraint_prevents_duplicate_prefix_date()
    {
        // Act: insert the first record — should succeed
        DB::table('document_sequences')->insert([
            'prefix' => 'SO',
            'date' => '2026-06-11',
            'counter' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Verify it exists
        $this->assertEquals(1, DB::table('document_sequences')->count());

        // Assert: inserting a second record with the same (prefix, date) must
        // violate the unique constraint defined in the migration
        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('document_sequences')->insert([
            'prefix' => 'SO',
            'date' => '2026-06-11',
            'counter' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // If we reach here the unique constraint is missing
        $this->fail('Unique constraint on (prefix, date) did not prevent a duplicate insert.');
    }
}
