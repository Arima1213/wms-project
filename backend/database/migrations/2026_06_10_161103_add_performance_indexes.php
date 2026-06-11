<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add performance indexes for frequently-queried columns.
     */
    public function up(): void
    {
        // Stock transactions — most queries filter by product, warehouse, date, type
        Schema::table('stock_transactions', function (Blueprint $table) {
            $table->index('product_id', 'idx_st_trans_product');
            $table->index('warehouse_id', 'idx_st_trans_warehouse');
            $table->index('created_at', 'idx_st_trans_created');
            $table->index('transaction_type', 'idx_st_trans_type');
            $table->index(['product_id', 'warehouse_id', 'created_at'], 'idx_st_trans_product_wh_date');
        });

        // Inventory — filtered by product + warehouse (the core lookup)
        Schema::table('inventory', function (Blueprint $table) {
            $table->index('product_id', 'idx_inv_product');
            $table->index('warehouse_id', 'idx_inv_warehouse');
            $table->index(['product_id', 'warehouse_id', 'batch_number'], 'idx_inv_product_wh_batch');
        });

        // Inbounds — status filtering + warehouse scoping
        Schema::table('inbounds', function (Blueprint $table) {
            $table->index('status', 'idx_inbound_status');
            $table->index('inbound_number', 'idx_inbound_number');
            $table->index(['warehouse_id', 'status', 'created_at'], 'idx_inbound_wh_status_date');
        });

        // Outbounds — same pattern
        Schema::table('outbounds', function (Blueprint $table) {
            $table->index('status', 'idx_outbound_status');
            $table->index('outbound_number', 'idx_outbound_number');
            $table->index(['warehouse_id', 'status', 'created_at'], 'idx_outbound_wh_status_date');
        });

        // Products — SKU/Barcode lookups
        Schema::table('products', function (Blueprint $table) {
            $table->index('sku', 'idx_product_sku');
            $table->index('barcode', 'idx_product_barcode');
            $table->index('category_id', 'idx_product_category');
        });

        // Transfers — source/dest warehouse lookups
        // source_warehouse_id+status already indexed in create migration
        // Adding dest_warehouse_id+status for reverse lookup
        Schema::table('transfers', function (Blueprint $table) {
            $table->index(['dest_warehouse_id', 'status'], 'idx_transfer_dest_status');
        });

        // Stock opnames — warehouse scoping
        Schema::table('stock_opnames', function (Blueprint $table) {
            $table->index(['warehouse_id', 'status', 'created_at'], 'idx_opname_wh_status_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_st_trans_product');
            $table->dropIndex('idx_st_trans_warehouse');
            $table->dropIndex('idx_st_trans_created');
            $table->dropIndex('idx_st_trans_type');
            $table->dropIndex('idx_st_trans_product_wh_date');
        });

        Schema::table('inventory', function (Blueprint $table) {
            $table->dropIndex('idx_inv_product');
            $table->dropIndex('idx_inv_warehouse');
            $table->dropIndex('idx_inv_product_wh_batch');
        });

        Schema::table('inbounds', function (Blueprint $table) {
            $table->dropIndex('idx_inbound_status');
            $table->dropIndex('idx_inbound_number');
            $table->dropIndex('idx_inbound_wh_status_date');
        });

        Schema::table('outbounds', function (Blueprint $table) {
            $table->dropIndex('idx_outbound_status');
            $table->dropIndex('idx_outbound_number');
            $table->dropIndex('idx_outbound_wh_status_date');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_product_sku');
            $table->dropIndex('idx_product_barcode');
            $table->dropIndex('idx_product_category');
        });

        Schema::table('transfers', function (Blueprint $table) {
            $table->dropIndex('idx_transfer_from_status');
            $table->dropIndex('idx_transfer_to_status');
        });

        Schema::table('stock_opnames', function (Blueprint $table) {
            $table->dropIndex('idx_opname_wh_status_date');
        });
    }
};
