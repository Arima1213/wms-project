<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inbounds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('inbound_number', 30)->unique();
            $table->uuid('warehouse_id');
            $table->uuid('supplier_id')->nullable();
            $table->uuid('reference_number')->nullable(); // PO/SJ reference
            $table->enum('type', ['purchase', 'return', 'transfer_in', 'adjustment'])->default('purchase');
            $table->enum('status', ['draft', 'submitted', 'partial', 'received', 'cancelled'])->default('draft');
            $table->date('expected_date')->nullable();
            $table->date('received_date')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('received_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            $table->foreign('received_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inbounds');
    }
};
