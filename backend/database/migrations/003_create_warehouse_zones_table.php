<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->string('code', 10); // A, B, C, COLD
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('zone_type', ['fast_moving', 'slow_moving', 'heavy', 'cold', 'hazmat', 'general'])->default('general');
            $table->decimal('min_temp', 5, 2)->nullable();
            $table->decimal('max_temp', 5, 2)->nullable();
            $table->decimal('min_humidity', 5, 2)->nullable();
            $table->decimal('max_humidity', 5, 2)->nullable();
            $table->json('allowed_categories')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_zones');
    }
};
