<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbounds', function (Blueprint $table) {
            $table->id();
            $table->string('outbound_number', 50)->unique();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['draft', 'picking', 'picked', 'shipped', 'delivered', 'cancelled'])->default('draft');
            $table->enum('destination_type', ['sales_order', 'production', 'transfer_out', 'sample', 'other'])->default('sales_order');
            $table->string('destination_reference', 100)->nullable();
            $table->string('customer_name', 200)->nullable();
            $table->text('shipping_address')->nullable();
            $table->date('expected_date')->nullable();
            $table->date('shipped_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbounds');
    }
};
