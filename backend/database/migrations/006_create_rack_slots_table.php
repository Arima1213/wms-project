<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rack_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rack_level_id')->constrained('rack_levels')->cascadeOnDelete();
            $table->string('slot_code', 20); // e.g. A-01-L2-S3
            $table->tinyInteger('column_number');
            $table->decimal('width_cm', 6, 2)->default(30);
            $table->decimal('depth_cm', 6, 2)->default(30);
            $table->decimal('height_cm', 6, 2)->default(30);
            $table->decimal('max_weight_kg', 8, 2)->default(50);
            $table->enum('slot_type', ['fixed', 'floating', 'reserved'])->default('floating');
            $table->enum('status', ['empty', 'partial', 'full', 'reserved'])->default('empty');
            $table->timestamps();
            $table->unique(['rack_level_id', 'column_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rack_slots');
    }
};
