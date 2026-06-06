<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 50)->unique();
            $table->string('barcode', 50)->nullable()->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->string('unit',20)->default('pcs'); // pcs, kg, liter
            $table->decimal('length_cm', 8, 3)->nullable();
            $table->decimal('width_cm', 8, 3)->nullable();
            $table->decimal('height_cm', 8, 3)->nullable();
            $table->decimal('weight_kg', 8, 3)->nullable();
            $table->decimal('min_stock', 12, 3)->default(0);
            $table->decimal('max_stock', 12, 3)->default(0);
            $table->decimal('safety_stock', 12, 3)->default(0);
            $table->decimal('reorder_point', 12, 3)->default(0);
            $table->decimal('purchase_price', 12, 2)->default(0);
            $table->decimal('selling_price', 12, 2)->default(0);
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
