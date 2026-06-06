<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Product Prices (supplier pricing)
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('type', 30)->default('purchase'); // purchase, selling, special
            $table->decimal('price', 15, 2);
            $table->decimal('min_qty', 12, 2)->default(1);
            $table->date('effective_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
            $table->index(['product_id', 'supplier_id']);
            $table->index('effective_date');
        });

        // Stock (current inventory per location)
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('rack_slot_id')->nullable();
            $table->string('batch_number')->nullable();
            $table->date('manufacture_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->decimal('reserved_quantity', 12, 2)->default(0);
            $table->decimal('available_quantity', 12, 2)->default(0);
            $table->decimal('cost', 15, 4)->default(0);
            $table->decimal('avg_cost', 15, 4)->default(0);
            $table->string('currency', 3)->default('IDR');
            $table->string('status', 20)->default('available'); // available, reserved, quarantine, damaged
            $table->string('condition', 20)->default('good'); // good, damaged, expired
            $table->string('lot_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('rack_slot_id')->references('id')->on('rack_slots')->onDelete('set null');
            $table->index(['warehouse_id', 'product_id']);
            $table->index('batch_number');
            $table->index('expiry_date');
            $table->index('status');
        });

        // Stock Movement History
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('type', 30); // in, out, transfer, adjustment, opname
            $table->string('reference_type', 50)->nullable(); // inbound, outbound, transfer, adjustment, opname
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('to_warehouse_id')->nullable();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('rack_slot_id')->nullable();
            $table->unsignedBigInteger('to_rack_slot_id')->nullable();
            $table->decimal('quantity', 12, 2);
            $table->decimal('before_quantity', 12, 2)->nullable();
            $table->decimal('after_quantity', 12, 2)->nullable();
            $table->decimal('cost', 15, 4)->nullable();
            $table->string('batch_number')->nullable();
            $table->date('manufacture_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('status', 20)->default('completed');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamp('created_at');

            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->foreign('to_warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->index(['warehouse_id', 'product_id']);
            $table->index('type');
            $table->index('reference_type');
            $table->index('created_at');
        });

        // Units (measurement units)
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('symbol', 10);
            $table->string('type', 30)->default('quantity'); // quantity, weight, volume, length
            $table->decimal('conversion_factor', 12, 6)->default(1);
            $table->unsignedBigInteger('base_unit_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('base_unit_id')->references('id')->on('units')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stocks');
        Schema::dropIfExists('product_prices');
    }
};
