<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slot_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('slot_id')->constrained('rack_slots')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('product_batches')->nullOnDelete();
            $table->decimal('quantity', 12, 4)->default(0);
            $table->foreignId('uom_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity_in_base_uom', 12, 4)->default(0);
            $table->decimal('unit_cost', 15, 4)->nullable();
            $table->decimal('total_cost', 15, 4)->nullable();
            $table->date('expiry_date')->nullable();
            $table->boolean('is_current')->default(true);
            $table->timestamps();
            $table->index(['slot_id', 'is_current']);
            $table->index(['product_id', 'is_current']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slot_stocks');
    }
};
