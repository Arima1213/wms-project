<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transactions', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->unique();
            // GR=Goods Receipt, GI=Goods Issue, TR=Transfer, LT=Location Transfer,
            // SO=Stock Opname, ADJ+=Adj Plus, ADJ-=Adj Minus, RS=Reserve, RC=Release
            $table->enum('transaction_type', ['GR', 'GI', 'TR', 'LT', 'SO', 'ADJ+', 'ADJ-', 'RS', 'RC']);
            $table->string('transactionable_type', 100)->nullable();
            $table->unsignedBigInteger('transactionable_id')->nullable();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('source_slot_id')->nullable();
            $table->unsignedBigInteger('dest_slot_id')->nullable();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('source_warehouse_id')->nullable();
            $table->unsignedBigInteger('dest_warehouse_id')->nullable();
            $table->unsignedBigInteger('batch_id')->nullable();
            $table->decimal('quantity', 12, 4);
            $table->unsignedBigInteger('uom_id')->nullable();
            $table->decimal('quantity_in_base_uom', 12, 4);
            $table->decimal('stock_before', 12, 4)->default(0);
            $table->decimal('stock_after', 12, 4)->default(0);
            $table->decimal('unit_cost', 15, 4)->nullable();
            $table->decimal('total_cost', 15, 4)->nullable();
            $table->string('reference_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('created_at');

            $table->foreign('source_slot_id')->references('id')->on('rack_slots')->nullOnDelete();
            $table->foreign('dest_slot_id')->references('id')->on('rack_slots')->nullOnDelete();
            $table->foreign('source_warehouse_id')->references('id')->on('warehouses')->nullOnDelete();
            $table->foreign('dest_warehouse_id')->references('id')->on('warehouses')->nullOnDelete();
            $table->foreign('batch_id')->references('id')->on('product_batches')->nullOnDelete();
            $table->foreign('uom_id')->references('id')->on('uoms')->nullOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['warehouse_id', 'transaction_type', 'created_at']);
            $table->index(['product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transactions');
    }
};
