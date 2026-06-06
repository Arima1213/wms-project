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
            $table->string('code', 30)->unique();
            $table->string('sku', 30)->unique();
            $table->string('barcode', 50)->nullable()->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('uoms')->nullOnDelete();
            $table->decimal('length_cm', 8, 2)->nullable();
            $table->decimal('width_cm', 8, 2)->nullable();
            $table->decimal('height_cm', 8, 2)->nullable();
            $table->decimal('weight_kg', 8, 3)->nullable();
            $table->decimal('min_stock', 12, 4)->default(0);
            $table->decimal('max_stock', 12, 4)->default(0);
            $table->decimal('reorder_point', 12, 4)->default(0);
            $table->decimal('safety_stock', 12, 4)->default(0);
            $table->enum('product_type', ['standard', 'oversized', 'hazmat', 'cold'])->default('standard');
            $table->boolean('track_batch')->default(false);
            $table->boolean('track_expiry')->default(false);
            $table->string('hs_code', 20)->nullable();
            $table->string('image_url')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['is_active', 'sku']);
            $table->index(['category_id', 'is_active']);
        });

        Schema::create('product_barcodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('barcode', 100);
            $table->string('type', 20)->default('EAN13');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->unique(['barcode', 'type']);
            $table->index('product_id');
        });

        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('batch_number', 100);
            $table->date('expiry_date')->nullable()->index();
            $table->date('manufacture_date')->nullable();
            $table->string('origin_country', 50)->nullable();
            $table->decimal('cost', 15, 4)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['product_id', 'batch_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_batches');
        Schema::dropIfExists('product_barcodes');
        Schema::dropIfExists('products');
    }
};
