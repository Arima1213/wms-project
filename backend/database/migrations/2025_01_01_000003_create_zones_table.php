<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->string('code', 10);
            $table->string('name');
            $table->enum('zone_type', ['fast_moving', 'slow_moving', 'heavy', 'cold', 'hazmat'])->default('fast_moving');
            $table->json('temperature_range')->nullable();
            $table->json('humidity_range')->nullable();
            $table->json('allowed_product_types')->nullable();
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#3B82F6');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['warehouse_id', 'code']);
            $table->index(['warehouse_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zones');
    }
};
