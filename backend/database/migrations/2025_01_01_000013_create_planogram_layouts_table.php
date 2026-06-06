<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planogram_layouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->integer('canvas_width')->default(5000);
            $table->integer('canvas_height')->default(3000);
            $table->integer('grid_size')->default(50);
            $table->integer('version')->default(1);
            $table->json('layout_data')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['warehouse_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planogram_layouts');
    }
};
