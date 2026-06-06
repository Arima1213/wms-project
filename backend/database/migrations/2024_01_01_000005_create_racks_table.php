<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('racks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('warehouse_id');
            $table->string('code', 30);
            $table->string('name');
            $table->integer('levels')->default(4); // jumlah level (tingkat)
            $table->integer('slots_per_level')->default(10); // slot per level
            $table->decimal('max_capacity_kg', 10, 2)->default(1000);
            $table->enum('status', ['available', 'full', 'maintenance', 'retired'])->default('available');
            $table->json('position')->nullable(); // {"x": 100, "y": 200, "z": 0} 3D coordinates
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->unique(['warehouse_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('racks');
    }
};
