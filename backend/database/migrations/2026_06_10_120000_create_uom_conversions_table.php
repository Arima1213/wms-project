<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uom_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_uom_id')->constrained('uoms')->cascadeOnDelete();
            $table->foreignId('to_uom_id')->constrained('uoms')->cascadeOnDelete();
            $table->decimal('conversion_factor', 12, 4)->default(1.0000);
            $table->timestamps();

            $table->unique(['product_id', 'from_uom_id', 'to_uom_id'], 'uom_conv_unique');
            $table->index('product_id');
            $table->index('from_uom_id');
            $table->index('to_uom_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uom_conversions');
    }
};
