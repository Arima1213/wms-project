<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rack_slot_id')->nullable()->constrained('rack_slots')->nullOnDelete();
            $table->string('batch_number', 50)->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('quantity', 12, 3)->default(0);
            $table->decimal('reserved_quantity', 12, 3)->default(0);
            $table->decimal('available_quantity', 12, 3)->default(0);
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->timestamps();
            $table->unique(['warehouse_id', 'product_id', 'rack_slot_id', 'batch_number'], 'inv_warehouse_product_slot_batch');
            $table->index(['warehouse_id', 'product_id']);
 });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};
