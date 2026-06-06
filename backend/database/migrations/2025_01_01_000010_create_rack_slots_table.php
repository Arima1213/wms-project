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
            $table->foreignId('rack_level_id')->constrained()->cascadeOnDelete();
            $table->string('slot_code', 20)->unique();
            $table->tinyInteger('slot_number');
            $table->decimal('max_weight_kg', 8, 2)->nullable();
            $table->integer('max_volume_cm3')->nullable();
            $table->enum('slot_type', ['standard', 'oversized', 'hazmat', 'cold'])->default('standard');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_reserved')->default(false);
            $table->timestamp('reserved_until')->nullable();
            $table->string('reserved_for', 100)->nullable();
            $table->float('visual_x')->nullable();
            $table->float('visual_y')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['rack_level_id', 'is_active']);
            $table->index(['slot_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rack_slots');
    }
};
