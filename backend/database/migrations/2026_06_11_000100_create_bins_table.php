<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rack_id')->constrained('racks')->cascadeOnDelete();
            $table->string('code', 50);
            $table->integer('level')->default(1);
            $table->integer('position')->default(0);
            $table->enum('bin_type', ['storage', 'picking', 'receiving', 'shipping', 'overflow', 'quarantine'])->default('storage');
            $table->decimal('max_weight', 12, 2)->nullable();
            $table->decimal('max_volume', 12, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['rack_id', 'code']);
            $table->index(['rack_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bins');
    }
};
