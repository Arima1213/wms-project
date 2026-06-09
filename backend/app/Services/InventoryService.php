<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Inventory;
use App\Models\StockTransaction;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Collection;

class InventoryService
{
    public function list(array $filters): Collection
    {
        $query = Inventory::query();
        return $query->get();
    }

    public function create(array $data): Inventory
    {
        return Inventory::create($data);
    }

    public function update(int $id, array $data): Inventory
    {
        $item = Inventory::findOrFail($id);
        $item->update($data);
        return $item->fresh();
    }

    public function delete(int $id): void
    {
        Inventory::findOrFail($id)->delete();
    }

    /**
     * Increase inventory stock and record a GR (Goods Receipt) transaction.
     *
     * @param int $productId
     * @param int $warehouseId
     * @param float $quantity
     * @param int $userId
     * @param array $options [batch_number, expiry_date, reference_type, reference_id, reference_number, notes]
     * @return Inventory
     */
    public function receiveStock(int $productId, int $warehouseId, float $quantity, int $userId, array $options = []): Inventory
    {
        $inventory = Inventory::firstOrNew([
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'batch_number' => $options['batch_number'] ?? null,
            'rack_slot_id' => null, // Can be mapped to a receiving slot later
        ]);

        $stockBefore = $inventory->quantity ?? 0;
        
        $inventory->quantity += $quantity;
        $inventory->available_quantity = ($inventory->available_quantity ?? 0) + $quantity;
        
        if (!empty($options['expiry_date'])) {
            $inventory->expiry_date = $options['expiry_date'];
        }
        $inventory->save();

        StockTransaction::create([
            'ulid' => (string) Str::ulid(),
            'transaction_type' => 'GR',
            'transactionable_type' => $options['reference_type'] ?? null,
            'transactionable_id' => $options['reference_id'] ?? null,
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'dest_warehouse_id' => $warehouseId,
            'batch_id' => null, // Should match with ProductBatch logic if needed
            'quantity' => $quantity,
            'quantity_in_base_uom' => $quantity,
            'stock_before' => $stockBefore,
            'stock_after' => $inventory->quantity,
            'reference_number' => $options['reference_number'] ?? null,
            'notes' => $options['notes'] ?? 'Stock Received',
            'created_by' => $userId,
            'created_at' => now(),
        ]);

        return $inventory;
    }

    /**
     * Decrease inventory stock and record a GI (Goods Issue) transaction.
     *
     * Uses FIFO (First In, First Out) or FEFO (First Expiry, First Out) strategy
     * to pick stock from the oldest batches first, creating one stock movement
     * record per inventory batch consumed.
     *
     * @param int $productId
     * @param int $warehouseId
     * @param float $quantity
     * @param int $userId
     * @param array $options [strategy, reference_type, reference_id, reference_number, notes]
     *                       strategy: 'fifo' (default) or 'fefo'
     * @return array<int, StockTransaction> Array of movement records (one per inventory batch)
     * @throws InsufficientStockException
     */
    public function issueStock(int $productId, int $warehouseId, float $quantity, int $userId, array $options = []): array
    {
        $strategy = $options['strategy'] ?? 'fifo';

        // Ambil semua inventory records yang masih punya stok
        $query = Inventory::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('quantity', '>', 0);

        if ($strategy === 'fefo') {
            // FEFO: item dengan expiry terdekat duluan, lalu yang paling lama diterima
            $query->orderByRaw('expiry_date ASC NULLS LAST')
                  ->orderBy('created_at', 'ASC')
                  ->orderBy('id', 'ASC');
        } else {
            // FIFO (default): item paling lama diterima duluan
            $query->orderBy('created_at', 'ASC')
                  ->orderBy('id', 'ASC');
        }

        $records = $query->get();

        $totalAvailable = $records->sum('quantity');
        if ($totalAvailable < $quantity) {
            throw new InsufficientStockException(
                "Insufficient stock for product ID {$productId} in warehouse {$warehouseId}. "
                    . "Requested: {$quantity}, Available: {$totalAvailable}",
                $productId,
                $warehouseId,
                $quantity,
                $totalAvailable
            );
        }

        $remainingQty = $quantity;
        $movements = [];

        foreach ($records as $inventory) {
            if ($remainingQty <= 0) {
                break;
            }

            $taken = min($inventory->quantity, $remainingQty);
            $stockBefore = $inventory->quantity;

            $inventory->quantity -= $taken;
            $inventory->available_quantity -= $taken;
            $inventory->save();

            $movements[] = StockTransaction::create([
                'ulid' => (string) Str::ulid(),
                'transaction_type' => 'GI',
                'transactionable_type' => $options['reference_type'] ?? null,
                'transactionable_id' => $options['reference_id'] ?? null,
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'source_warehouse_id' => $warehouseId,
                'batch_id' => null,
                'quantity' => $taken,
                'quantity_in_base_uom' => $taken,
                'stock_before' => $stockBefore,
                'stock_after' => $inventory->quantity,
                'reference_number' => $options['reference_number'] ?? null,
                'notes' => $options['notes'] ?? 'Stock Issued',
                'created_by' => $userId,
                'created_at' => now(),
            ]);

            $remainingQty -= $taken;
        }

        return $movements;
    }

    /**
     * Transfer stock between warehouses and log a TR transaction.
     *
     * @param int $productId
     * @param int $sourceWarehouseId
     * @param int $destWarehouseId
     * @param float $quantity
     * @param int $userId
     * @param array $options
     * @return void
     * @throws \Exception
     */
    public function transferStock(int $productId, int $sourceWarehouseId, int $destWarehouseId, float $quantity, int $userId, array $options = []): void
    {
        // Create a single TR record instead of GI + GR.

        // 2. We should ideally update the GI transaction type to TR, but for simplicity, 
        // we can just use the receiveStock/issueStock directly, OR write custom logic.
        // For a true TR, it's better to write custom logic to create exactly one TR record.
        
        // Let's create a single TR record instead of GI + GR.
        // Wait, issueStock and receiveStock will create GI and GR.
        // We will do a custom manual adjustment here to avoid double logging.
        
        $sourceInventory = Inventory::where(['product_id' => $productId, 'warehouse_id' => $sourceWarehouseId])->first();
        if (!$sourceInventory || $sourceInventory->quantity < $quantity) {
            throw new \Exception("Insufficient stock for transfer.");
        }

        $sourceBefore = $sourceInventory->quantity;
        $sourceInventory->quantity -= $quantity;
        $sourceInventory->available_quantity -= $quantity;
        $sourceInventory->save();

        $destInventory = Inventory::firstOrNew([
            'product_id' => $productId,
            'warehouse_id' => $destWarehouseId,
        ]);
        $destBefore = $destInventory->quantity ?? 0;
        $destInventory->quantity += $quantity;
        $destInventory->available_quantity = ($destInventory->available_quantity ?? 0) + $quantity;
        $destInventory->save();

        StockTransaction::create([
            'ulid' => (string) Str::ulid(),
            'transaction_type' => 'TR',
            'transactionable_type' => $options['reference_type'] ?? null,
            'transactionable_id' => $options['reference_id'] ?? null,
            'product_id' => $productId,
            'warehouse_id' => $sourceWarehouseId, // Record against source
            'source_warehouse_id' => $sourceWarehouseId,
            'dest_warehouse_id' => $destWarehouseId,
            'quantity' => $quantity,
            'quantity_in_base_uom' => $quantity,
            'stock_before' => $sourceBefore,
            'stock_after' => $sourceInventory->quantity,
            'reference_number' => $options['reference_number'] ?? null,
            'notes' => $options['notes'] ?? 'Warehouse Transfer',
            'created_by' => $userId,
            'created_at' => now(),
        ]);
    }

    /**
     * Adjust stock directly (e.g. for Stock Opname) and log ADJ+ or ADJ-
     *
     * @param int $productId
     * @param int $warehouseId
     * @param float $differenceQty (positive or negative)
     * @param int $userId
     * @param array $options
     * @return void
     */
    public function adjustStock(int $productId, int $warehouseId, float $differenceQty, int $userId, array $options = []): void
    {
        if ($differenceQty == 0) return;

        $inventory = Inventory::firstOrNew([
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
        ]);

        $stockBefore = $inventory->quantity ?? 0;
        $newQuantity = ($inventory->quantity ?? 0) + $differenceQty;
        $newAvailable = ($inventory->available_quantity ?? 0) + $differenceQty;

        // Throw exception instead of silent clamp — negative stock is a data integrity violation
        if ($newQuantity < 0) {
            throw new InsufficientStockException(
                "Insufficient stock for product {$productId} in warehouse {$warehouseId}. "
                    . "Adjustment would result in {$newQuantity} (requested: {$differenceQty}, current: " . ($inventory->quantity ?? 0) . ")",
                $productId,
                $warehouseId,
                $differenceQty,
                $inventory->quantity ?? 0
            );
        }

        $inventory->quantity = $newQuantity;
        $inventory->available_quantity = $newAvailable;

        $inventory->save();

        StockTransaction::create([
            'ulid' => (string) Str::ulid(),
            'transaction_type' => $differenceQty > 0 ? 'ADJ+' : 'ADJ-',
            'transactionable_type' => $options['reference_type'] ?? null,
            'transactionable_id' => $options['reference_id'] ?? null,
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'quantity' => abs($differenceQty),
            'quantity_in_base_uom' => abs($differenceQty),
            'stock_before' => $stockBefore,
            'stock_after' => $inventory->quantity,
            'reference_number' => $options['reference_number'] ?? null,
            'notes' => $options['notes'] ?? 'Stock Opname Adjustment',
            'created_by' => $userId,
            'created_at' => now(),
        ]);
    }
}
