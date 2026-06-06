<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbound_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('outbound_id');
            $table->uuid('product_id');
            $table->string('quantity', 20);
            $table->uuid('unit_id');
            $table->uuid('rack_id')->nullable(); // source rack
            $table->string('batch_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('outbound_id')->references('id')->on('outbounds')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products');
            $table->foreign('rack_id')->references('id')->on('racks')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbound_items');
    }
};
