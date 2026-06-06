<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_opname_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_opname_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rack_slot_id')->nullable()->constrained('rack_slots')->nullOnDelete();
            $table->string('batch_number', 50)->nullable();
            $table->decimal('system_quantity', 12, 3);
            $table->decimal('counted_quantity', 12, 3)->nullable();
            $table->decimal('variance', 12, 3)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opname_items');
    }
};
