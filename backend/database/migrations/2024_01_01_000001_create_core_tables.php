<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // === CORE TABLES ===

        // Users
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone', 20)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('avatar')->nullable();
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index('email');
            $table->index('is_active');
        });

        // Warehouses
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type', 30)->default('warehouse'); // warehouse, distribution, cross-dock
            $table->string('status', 20)->default('active'); // active, inactive, maintenance
            $table->json('address');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->json('operating_hours')->nullable();
            $table->json('contact');
            $table->decimal('max_capacity', 12, 2)->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
            $table->index('status');
            $table->index('type');
        });

        // Categories
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('code', 30)->unique();
            $table->text('description')->nullable();
            $table->string('type', 30)->default('product'); // product, service, asset
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('level')->default(0);
            $table->string('icon')->nullable();
            $table->string('color', 7)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('parent_id')->references('id')->on('categories')->onDelete('set null');
            $table->index('parent_id');
            $table->index('type');
        });

        // Products
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('sku')->unique();
            $table->string('barcode')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('type', 30)->default('goods'); // goods, raw_material, finished_goods
            $table->string('unit'); // pcs, kg, liter, meter, etc.
            $table->decimal('length', 8, 2)->nullable();
            $table->decimal('width', 8, 2)->nullable();
            $table->decimal('height', 8, 2)->nullable();
            $table->decimal('weight', 10, 3)->nullable();
            $table->decimal('min_stock', 12, 2)->default(0);
            $table->decimal('max_stock', 12, 2)->nullable();
            $table->decimal('reorder_point', 12, 2)->default(0);
            $table->decimal('safety_stock', 12, 2)->default(0);
            $table->string('image')->nullable();
            $table->json('images')->nullable();
            $table->json('attributes')->nullable();
            $table->string('abc_classification', 1)->nullable(); // A, B, C
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_serial')->default(false);
            $table->boolean('has_expiry')->default(false);
            $table->integer('shelf_life_days')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
            $table->index('sku');
            $table->index('category_id');
            $table->index('type');
            $table->index('abc_classification');
        });

        // Suppliers
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('company')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('tax_id')->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('country', 50)->default('Indonesia');
            $table->decimal('credit_limit', 15, 2)->nullable();
            $table->integer('payment_term_days')->default(30);
            $table->string('bank_account')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('category', 50)->nullable();
            $table->decimal('rating', 3, 2)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
            $table->index('city');
            $table->index('is_active');
        });

        // Customers
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('type', 30)->default('individual'); // individual, company, government
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('tax_id')->nullable();
            $table->text('billing_address')->nullable();
            $table->string('billing_city', 100)->nullable();
            $table->string('billing_province', 100)->nullable();
            $table->string('billing_postal_code', 10)->nullable();
            $table->text('shipping_address')->nullable();
            $table->string('shipping_city', 100)->nullable();
            $table->string('shipping_province', 100)->nullable();
            $table->string('shipping_postal_code', 10)->nullable();
            $table->decimal('credit_limit', 15, 2)->nullable();
            $table->integer('payment_term_days')->default(30);
            $table->decimal('balance', 15, 2)->default(0);
            $table->decimal('total_receivable', 15, 2)->default(0);
            $table->string('price_tier', 20)->default('standard'); // standard, silver, gold, platinum
            $table->integer('loyalty_points')->default(0);
            $table->string('marketing_id')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
            $table->index('type');
            $table->index('price_tier');
        });

        // Racks
        Schema::create('racks', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('warehouse_id');
            $table->string('code', 30);
            $table->string('name');
            $table->string('type', 30)->default('standard'); // standard, drive_in, push_back, gravity
            $table->string('zone', 30)->nullable();
            $table->integer('levels')->default(4);
            $table->integer('slots_per_level')->default(10);
            $table->decimal('max_load_per_slot', 10, 2)->nullable();
            $table->string('material', 30)->nullable(); // steel, aluminum, wood
            $table->year('manufacture_year')->nullable();
            $table->decimal('length', 8, 2)->nullable();
            $table->decimal('width', 8, 2)->nullable();
            $table->decimal('height', 8, 2)->nullable();
            $table->string('status', 20)->default('active');
            $table->json('position'); // {x, y, width, height, rotation}
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->unique(['warehouse_id', 'code']);
            $table->index('warehouse_id');
            $table->index('zone');
        });

        // Rack Levels
        Schema::create('rack_levels', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('rack_id');
            $table->integer('level_number');
            $table->decimal('height', 8, 2)->nullable();
            $table->decimal('max_load', 10, 2)->nullable();
            $table->integer('slots')->default(1);
            $table->string('slot_type', 30)->default('bin'); // bin, shelf, pallet
            $table->timestamps();

            $table->foreign('rack_id')->references('id')->on('racks')->onDelete('cascade');
            $table->unique(['rack_id', 'level_number']);
            $table->index('rack_id');
        });

        // Rack Slots
        Schema::create('rack_slots', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('rack_level_id');
            $table->integer('slot_number');
            $table->string('code', 50);
            $table->string('barcode')->nullable();
            $table->string('status', 20)->default('empty'); // empty, occupied, reserved, damaged
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('stock_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('rack_level_id')->references('id')->on('rack_levels')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
            $table->unique(['rack_level_id', 'slot_number']);
            $table->unique('code');
            $table->index('rack_level_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rack_slots');
        Schema::dropIfExists('rack_levels');
        Schema::dropIfExists('racks');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('users');
    }
};