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
            $table->foreignId('zone_id')->constrained('warehouse_zones')->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name')->nullable();
            $table->integer('pos_x')->default(0);
            $table->integer('pos_y')->default(0);
            $table->decimal('width_cm', 8, 2)->default(100);
            $table->decimal('depth_cm', 8, 2)->default(50);
            $table->decimal('height_cm', 8, 2)->default(200);
            $table->integer('levels')->default(3);
            $table->integer('columns_per_level')->default(4);
            $table->decimal('max_weight_kg', 8, 2)->default(500);
            $table->enum('orientation', ['horizontal', 'vertical'])->default('horizontal');
            $table->timestamps();
            $table->unique(['zone_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('racks');
    }
};
