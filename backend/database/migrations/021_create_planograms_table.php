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
            $table->string('version', 20)->default('1.0');
            $table->json('canvas_data'); // Full canvas state: racks, zones, annotations
            $table->json('canvas_settings')->nullable(); // grid size, background
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->text('change_summary')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planograms');
    }
};
