<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Inter-warehouse Transfers
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number', 30)->unique();
            $table->foreignId('source_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('dest_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected', 'in_transit', 'completed', 'cancelled'])->default('pending');
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->date('expected_date')->nullable();
            $table->date('completed_date')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['source_warehouse_id', 'status']);
            $table->index('transfer_number');
        });

        // Transfer Items
        Schema::create('transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 12, 4);
            $table->decimal('received_qty', 12, 4)->default(0);
            $table->unsignedBigInteger('source_slot_id')->nullable();
            $table->unsignedBigInteger('dest_slot_id')->nullable();
            $table->string('batch_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->foreign('source_slot_id')->references('id')->on('rack_slots')->nullOnDelete();
            $table->foreign('dest_slot_id')->references('id')->on('rack_slots')->nullOnDelete();
            $table->index('transfer_id');
        });

        // Stock Opname
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->string('opname_number', 30)->unique();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('zone_id')->nullable();
            $table->enum('type', ['full', 'partial', 'cycle'])->default('full');
            $table->enum('status', ['draft', 'in_progress', 'submitted', 'approved', 'cancelled'])->default('draft');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('zone_id')->references('id')->on('zones')->nullOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['warehouse_id', 'status']);
            $table->index('opname_number');
        });

        // Stock Opname Items
        Schema::create('stock_opname_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_opname_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('slot_id')->nullable();
            $table->decimal('system_qty', 12, 4);
            $table->decimal('counted_qty', 12, 4)->nullable();
            $table->decimal('variance', 12, 4)->nullable();
            $table->enum('variance_status', ['match', 'over', 'short'])->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('counted_by')->nullable();
            $table->timestamp('counted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('slot_id')->references('id')->on('rack_slots')->nullOnDelete();
            $table->foreign('counted_by')->references('id')->on('users')->nullOnDelete();
            $table->index('stock_opname_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opname_items');
        Schema::dropIfExists('stock_opnames');
        Schema::dropIfExists('transfer_items');
        Schema::dropIfExists('transfers');
    }
};
