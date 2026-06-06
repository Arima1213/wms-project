<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inbound_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('inbound_id');
            $table->uuid('product_id');
            $table->string('quantity', 20);
            $table->uuid('unit_id');
            $table->string('qty_received', 20)->default('0');
            $table->uuid('rack_id')->nullable();
            $table->string('batch_number')->nullable();
            $table->date('production_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('inbound_id')->references('id')->on('inbounds')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('rack_id')->references('id')->on('racks')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inbound_items');
    }
};
