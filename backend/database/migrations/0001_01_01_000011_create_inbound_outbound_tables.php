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
            $table->string('inbound_number', 30)->unique();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('source_type', ['purchase', 'transfer', 'return', 'other'])->default('purchase');
            $table->string('source_reference')->nullable();
            $table->enum('status', ['draft', 'pending', 'partial', 'received', 'cancelled'])->default('pending');
            $table->date('expected_date')->nullable();
            $table->date('received_date')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['warehouse_id', 'status']);
            $table->index('inbound_number');
        });

        // Inbound Items
        Schema::create('inbound_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inbound_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('expected_qty', 12, 4);
            $table->decimal('received_qty', 12, 4)->default(0);
            $table->decimal('accepted_qty', 12, 4)->default(0);
            $table->decimal('rejected_qty', 12, 4)->default(0);
            $table->decimal('unit_cost', 15, 4)->nullable();
            $table->string('batch_number')->nullable();
            $table->date('manufacture_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->unsignedBigInteger('dest_slot_id')->nullable();
            $table->enum('status', ['pending', 'partial', 'received', 'rejected'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
            $table->foreign('dest_slot_id')->references('id')->on('rack_slots')->nullOnDelete();
            $table->index('inbound_id');
        });

        // Outbounds (Delivery Orders)
        Schema::create('outbounds', function (Blueprint $table) {
            $table->id();
            $table->string('outbound_number', 30)->unique();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['sales', 'transfer', 'return_supplier', 'other'])->default('sales');
            $table->enum('status', ['draft', 'pending', 'picking', 'packed', 'shipped', 'delivered', 'cancelled'])->default('pending');
            $table->date('order_date')->nullable();
            $table->date('shipped_date')->nullable();
            $table->date('delivered_date')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('destination_name')->nullable();
            $table->text('destination_address')->nullable();
            $table->string('shipping_method', 50)->nullable();
            $table->string('tracking_number')->nullable();
            $table->decimal('shipping_cost', 15, 2)->nullable();
            $table->decimal('total_amount', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['warehouse_id', 'status']);
            $table->index('outbound_number');
        });

        // Outbound Items
        Schema::create('outbound_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outbound_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('ordered_qty', 12, 4);
            $table->decimal('picked_qty', 12, 4)->default(0);
            $table->decimal('shipped_qty', 12, 4)->default(0);
            $table->decimal('unit_price', 15, 4)->nullable();
            $table->unsignedBigInteger('source_slot_id')->nullable();
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->enum('status', ['pending', 'picked', 'packed', 'shipped'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->foreign('source_slot_id')->references('id')->on('rack_slots')->nullOnDelete();
            $table->index('outbound_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbound_items');
        Schema::dropIfExists('outbounds');
        Schema::dropIfExists('inbound_items');
        Schema::dropIfExists('inbounds');
    }
};
