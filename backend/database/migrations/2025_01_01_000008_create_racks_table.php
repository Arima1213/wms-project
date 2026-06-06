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
    }

    public function down(): void
    {
        Schema::dropIfExists('racks');
    }
};
