<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planograms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->integer('canvas_width')->default(5000);
            $table->integer('canvas_height')->default(3000);
            $table->integer('grid_size')->default(50);
            $table->string('version', 20)->default('1.0');
            $table->json('canvas_data')->nullable();
            $table->json('canvas_settings')->nullable();
            $table->string('change_summary', 500)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique('warehouse_id');
        });

        Schema::create('planogram_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planogram_id')->constrained()->cascadeOnDelete();
            $table->string('version', 20);
            $table->json('canvas_data');
            $table->string('change_summary', 500)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['planogram_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planogram_snapshots');
        Schema::dropIfExists('planograms');
    }
};
