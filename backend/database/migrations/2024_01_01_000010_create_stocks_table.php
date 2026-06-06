<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('warehouse_id');
            $table->uuid('product_id');
            $table->uuid('rack_id')->nullable();
            $table->string('quantity', 20)->default('0');
            $table->uuid('unit_id');
            $table->string('batch_number')->nullable();
            $table->date('production_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('fifo_date')->nullable(); // untuk FIFO sorting
            $table->enum('status', ['available', 'reserved', 'quarantine', 'expired'])->default('available');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('rack_id')->references('id')->on('racks')->onDelete('set null');

            $table->unique(['warehouse_id', 'product_id', 'rack_id', 'batch_number'], 'stock_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
