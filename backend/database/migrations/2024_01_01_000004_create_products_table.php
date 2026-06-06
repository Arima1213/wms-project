<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('sku', 50)->unique();
            $table->string('barcode', 50)->nullable()->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->uuid('category_id')->nullable();
            $table->uuid('base_unit_id');
            $table->decimal('length', 8, 2)->nullable(); // cm
            $table->decimal('width', 8, 2)->nullable();
            $table->decimal('height', 8, 2)->nullable();
            $table->decimal('weight', 10, 3)->nullable(); // kg
            $table->string('image_url')->nullable();
            $table->string('min_stock_level', 20)->default('0'); // string for flex
            $table->string('max_stock_level', 20)->default('999999999');
            $table->integer('shelf_life_days')->nullable(); // null = tidak expire
            $table->enum('storage_type', ['dry', 'cold', 'frozen', 'hazmat'])->default('dry');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
            $table->foreign('base_unit_id')->references('id')->on('units');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
