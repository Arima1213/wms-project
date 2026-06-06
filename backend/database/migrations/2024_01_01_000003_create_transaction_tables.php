<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Inbounds (Goods Receipt)
        Schema::create('inbounds', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('inbound_number', 30)->unique();
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('type', 30)->default('purchase');
            $table->string('status', 20)->default('pending');
            $table->date('expected_date');
            $table->date('received_date')->nullable();
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
            $table->index('inbound_number');
            $table->index('status');
        });

        // Inbound Items
        Schema::create('inbound_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('inbound_id');
            $table->unsignedBigInteger('product_id');
            $table->decimal('ordered_qty', 12, 2);
            $table->decimal('received_qty', 12, 2)->default(0);
            $table->decimal('accepted_qty', 12, 2)->default(0);
            $table->decimal('rejected_qty', 12, 2)->default(0);
            $table->decimal('unit_cost', 15, 4)->nullable();
            $table->string('batch_number')->nullable();
            $table->date('manufacture_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('rack_slot_code')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->foreign('inbound_id')->references('id')->on('inbounds')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->index('inbound_id');
        });

        // Outbounds (Delivery Orders)
        Schema::create('outbounds', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('outbound_number', 30)->unique();
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('type', 30)->default('sales');
            $table->string('status', 20)->default('pending');
            $table->date('order_date');
            $table->date('shipped_date')->nullable();
            $table->date('delivered_date')->nullable();
            $table->unsignedBigInteger('sales_order_id')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('destination_name')->nullable();
            $table->text('destination_address')->nullable();
            $table->string('shipping_method', 50)->nullable();
            $table->string('tracking_number')->nullable();
            $table->decimal('shipping_cost', 15, 2)->nullable();
            $table->decimal('total_amount', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->index('outbound_number');
            $table->index('status');
        });

        // Outbound Items
        Schema::create('outbound_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('outbound_id');
            $table->unsignedBigInteger('product_id');
            $table->decimal('ordered_qty', 12, 2);
            $table->decimal('picked_qty', 12, 2)->default(0);
            $table->decimal('shipped_qty', 12, 2)->default(0);
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->string('rack_slot_code')->nullable();
            $table->string('lot_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->foreign('outbound_id')->references('id')->on('outbounds')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->index('outbound_id');
        });

        // Returns
        Schema::create('returns', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('return_number', 30)->unique();
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('type', 30)->default('customer');
            $table->string('reason', 50);
            $table->string('status', 20)->default('pending');
            $table->string('reference_type', 30)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('refund_amount', 15, 2)->nullable();
            $table->date('return_date');
            $table->date('processed_date')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->index('return_number');
            $table->index('status');
        });

        // Return Items
        Schema::create('return_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('return_id');
            $table->unsignedBigInteger('product_id');
            $table->decimal('quantity', 12, 2);
            $table->string('condition', 20)->default('good');
            $table->string('resolution', 30)->nullable();
            $table->decimal('refund_amount', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->foreign('return_id')->references('id')->on('returns')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });

        // Stock Opname
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('opname_number', 30)->unique();
            $table->unsignedBigInteger('warehouse_id');
            $table->string('type', 30)->default('full');
            $table->string('status', 20)->default('draft');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->index('opname_number');
            $table->index('status');
        });

        // Stock Opname Items
        Schema::create('stock_opname_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('stock_opname_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('rack_slot_id')->nullable();
            $table->decimal('system_quantity', 12, 2);
            $table->decimal('counted_quantity', 12, 2)->nullable();
            $table->decimal('variance', 12, 2)->nullable();
            $table->string('variance_status', 20)->nullable();
            $table->text('notes')->nullable();
            $table->string('counted_by')->nullable();
            $table->timestamp('counted_at')->nullable();
            $table->timestamps();
            $table->foreign('stock_opname_id')->references('id')->on('stock_opnames')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opname_items');
        Schema::dropIfExists('stock_opnames');
        Schema::dropIfExists('return_items');
        Schema::dropIfExists('returns');
        Schema::dropIfExists('outbound_items');
        Schema::dropIfExists('outbounds');
        Schema::dropIfExists('inbound_items');
        Schema::dropIfExists('inbounds');
    }
};