<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // notifications: index on (notifiable_id, notifiable_type, created_at)
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['notifiable_id', 'notifiable_type', 'created_at'], 'idx_notifications_notifiable_created');
        });

        // stock_opname_items: index on product_id
        Schema::table('stock_opname_items', function (Blueprint $table) {
            $table->index('product_id', 'idx_so_items_product_id');
        });

        // inbound_items: index on product_id
        Schema::table('inbound_items', function (Blueprint $table) {
            $table->index('product_id', 'idx_inbound_items_product_id');
        });

        // outbound_items: index on product_id
        Schema::table('outbound_items', function (Blueprint $table) {
            $table->index('product_id', 'idx_outbound_items_product_id');
        });

        // returns: index on warehouse_id
        Schema::table('returns', function (Blueprint $table) {
            $table->index('warehouse_id', 'idx_returns_warehouse_id');
        });

        // webhook_deliveries: index on next_retry_at
        Schema::table('webhook_deliveries', function (Blueprint $table) {
            $table->index('next_retry_at', 'idx_webhook_deliveries_next_retry_at');
        });

        // stock_transactions: index on (transactionable_type, transactionable_id)
        Schema::table('stock_transactions', function (Blueprint $table) {
            $table->index(['transactionable_type', 'transactionable_id'], 'idx_stock_transactions_transactionable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('idx_notifications_notifiable_created');
        });

        Schema::table('stock_opname_items', function (Blueprint $table) {
            $table->dropIndex('idx_so_items_product_id');
        });

        Schema::table('inbound_items', function (Blueprint $table) {
            $table->dropIndex('idx_inbound_items_product_id');
        });

        Schema::table('outbound_items', function (Blueprint $table) {
            $table->dropIndex('idx_outbound_items_product_id');
        });

        Schema::table('returns', function (Blueprint $table) {
            $table->dropIndex('idx_returns_warehouse_id');
        });

        Schema::table('webhook_deliveries', function (Blueprint $table) {
            $table->dropIndex('idx_webhook_deliveries_next_retry_at');
        });

        Schema::table('stock_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_stock_transactions_transactionable');
        });
    }
};
