<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('racks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name')->nullable();
            $table->float('canvas_x')->default(0);
            $table->float('canvas_y')->default(0);
            $table->decimal('width_cm', 8, 2)->default(300);
            $table->decimal('depth_cm', 8, 2)->default(80);
            $table->decimal('height_cm', 8, 2)->default(200);
            $table->enum('orientation', ['horizontal', 'vertical'])->default('horizontal');
            $table->decimal('max_weight_kg', 8, 2)->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['zone_id', 'code']);
            $table->index(['zone_id', 'is_active']);
        });

        Schema::create('rack_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rack_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('level_number');
            $table->decimal('height_cm', 8, 2)->default(100);
            $table->decimal('max_weight_kg', 8, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['rack_id', 'level_number']);
        });

        Schema::create('rack_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rack_level_id')->constrained()->cascadeOnDelete();
            $table->string('slot_code', 30)->unique();
            $table->tinyInteger('slot_number');
            $table->decimal('max_weight_kg', 8, 2)->nullable();
            $table->integer('max_volume_cm3')->nullable();
            $table->enum('slot_type', ['standard', 'oversized', 'hazmat', 'cold'])->default('standard');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_reserved')->default(false);
            $table->timestamp('reserved_until')->nullable();
            $table->string('reserved_for', 100)->nullable();
            $table->foreignId('fixed_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['rack_level_id', 'is_active']);
            $table->index('slot_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rack_slots');
        Schema::dropIfExists('rack_levels');
        Schema::dropIfExists('racks');
    }
};
