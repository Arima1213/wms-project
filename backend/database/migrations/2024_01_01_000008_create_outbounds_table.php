<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbounds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('outbound_number', 30)->unique();
            $table->uuid('warehouse_id');
            $table->uuid('customer_id')->nullable();
            $table->string('reference_number')->nullable();
            $table->enum('type', ['sale', 'return_out', 'transfer_out', 'sample', 'damaged'])->default('sale');
            $table->enum('status', ['draft', 'picked', 'shipped', 'delivered', 'cancelled'])->default('draft');
            $table->date('expected_date')->nullable();
            $table->date('shipped_date')->nullable();
            $table->date('delivered_date')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('prepared_by')->nullable();
            $table->uuid('shipped_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            $table->foreign('prepared_by')->references('id')->on('users');
            $table->foreign('shipped_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbounds');
    }
};
