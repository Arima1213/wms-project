<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id(); $table->string('name')->unique(); $table->string('display_name');
            $table->text('description')->nullable(); $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id(); $table->string('name')->unique(); $table->string('display_name');
            $table->text('description')->nullable(); $table->string('group');
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable(); $table->string('password');
            $table->string('phone')->nullable(); $table->string('avatar')->nullable();
            $table->foreignId('role_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true); $table->rememberToken(); $table->timestamps();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id(); $table->morphs('tokenable'); $table->string('name');
            $table->string('token', 64)->unique(); $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable(); $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('user_warehouse', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'warehouse_id']); $table->timestamps();
        });

        Schema::create('user_permissions', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'permission_id']);
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id(); $table->string('code')->unique(); $table->string('name');
            $table->text('address')->nullable(); $table->string('city',100)->nullable();
            $table->string('province',100)->nullable(); $table->string('postal_code',10)->nullable();
            $table->decimal('latitude',10,7)->nullable(); $table->decimal('longitude',10,7)->nullable();
            $table->jsonb('geofence')->nullable();
            $table->integer('total_racks')->default(0);
            $table->decimal('total_capacity',12,2)->default(0);
            $table->decimal('used_capacity',12,2)->default(0);
            $table->string('manager_name')->nullable(); $table->string('manager_phone',20)->nullable();
            $table->boolean('is_active')->default(true); $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id(); $table->string('name');
            $table->string('code')->nullable()->unique();
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->boolean('is_active')->default(true); $table->timestamps();
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id(); $table->string('code')->unique(); $table->string('name');
            $table->string('contact_person')->nullable(); $table->string('phone',20)->nullable();
            $table->string('email')->nullable(); $table->text('address')->nullable();
            $table->string('city',100)->nullable(); $table->string('province',100)->nullable();
            $table->string('postal_code',10)->nullable(); $table->string('tax_id',50)->nullable();
            $table->boolean('is_active')->default(true); $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id(); $table->string('code')->unique(); $table->string('name');
            $table->string('contact_person')->nullable(); $table->string('phone',20)->nullable();
            $table->string('email')->nullable(); $table->text('address')->nullable();
            $table->string('city',100)->nullable(); $table->string('province',100)->nullable();
            $table->string('postal_code',10)->nullable(); $table->string('tax_id',50)->nullable();
            $table->enum('customer_type', ['retail','wholesale','corporate'])->default('retail');
            $table->boolean('is_active')->default(true); $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id(); $table->string('code')->unique(); $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('unit',50)->default('pcs');
            $table->decimal('weight',10,3)->nullable(); $table->decimal('length',10,3)->nullable();
            $table->decimal('width',10,3)->nullable(); $table->decimal('height',10,3)->nullable();
            $table->decimal('min_stock',12,3)->default(0); $table->decimal('max_stock',12,3)->default(0);
            $table->string('barcode')->nullable(); $table->string('sku')->nullable();
            $table->string('image_url')->nullable(); $table->decimal('unit_cost',12,2)->nullable();
            $table->boolean('is_active')->default(true); $table->timestamps();
        });

        Schema::create('racks', function (Blueprint $table) {
            $table->id(); $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->string('code'); $table->string('name'); $table->string('zone')->nullable();
            $table->integer('levels')->default(4); $table->integer('racks_per_level')->default(1);
            $table->integer('total_bins')->default(0); $table->integer('used_bins')->default(0);
            $table->decimal('position_x')->nullable(); $table->decimal('position_y')->nullable();
            $table->boolean('is_active')->default(true); $table->timestamps();
            $table->unique(['warehouse_id','code']);
        });

        Schema::create('bins', function (Blueprint $table) {
            $table->id(); $table->foreignId('rack_id')->constrained()->cascadeOnDelete();
            $table->string('code'); $table->integer('level'); $table->integer('position');
            $table->enum('bin_type', ['pick','bulk','reserve'])->default('pick');
            $table->decimal('max_weight',10,2)->nullable(); $table->decimal('max_volume',10,2)->nullable();
            $table->boolean('is_active')->default(true); $table->timestamps();
            $table->unique(['rack_id','code']);
        });

        Schema::create('stocks', function (Blueprint $table) {
            $table->id(); $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bin_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity',12,3)->default(0);
            $table->decimal('reserved_quantity',12,3)->default(0);
            $table->decimal('available_quantity',12,3)->default(0);
            $table->string('batch_number')->nullable(); $table->date('expiry_date')->nullable();
            $table->string('location_code')->nullable(); $table->timestamps();
            $table->unique(['product_id','warehouse_id','bin_id','batch_number'],'stock_unique');
        });

        Schema::create('inbounds', function (Blueprint $table) {
            $table->id(); $table->string('reference_number')->unique();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('inbound_type',['purchase_return','transfer_in','production_in'])->default('purchase_return');
            $table->enum('status',['pending','received','cancelled'])->default('pending');
            $table->date('scheduled_date'); $table->date('received_date')->nullable();
            $table->text('notes')->nullable(); $table->integer('total_items')->default(0);
            $table->decimal('total_quantity',12,3)->default(0);
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable(); $table->timestamps();
        });

        Schema::create('inbound_items', function (Blueprint $table) {
            $table->id(); $table->foreignId('inbound_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bin_id')->nullable()->constrained()->nullOnDelete();
            $table->string('batch_number')->nullable(); $table->decimal('quantity',12,3);
            $table->decimal('accepted_quantity',12,3)->default(0);
            $table->decimal('rejected_quantity',12,3)->default(0);
            $table->date('expiry_date')->nullable(); $table->decimal('unit_cost',12,2)->nullable();
            $table->text('notes')->nullable(); $table->timestamps();
        });

        Schema::create('outbounds', function (Blueprint $table) {
            $table->id(); $table->string('reference_number')->unique();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('outbound_type',['sales','transfer_out','damaged'])->default('sales');
            $table->enum('status',['pending','picking','shipped','cancelled'])->default('pending');
            $table->date('order_date'); $table->date('shipped_date')->nullable();
            $table->text('notes')->nullable(); $table->integer('total_items')->default(0);
            $table->decimal('total_quantity',12,3)->default(0);
            $table->decimal('shipping_cost',12,2)->default(0);
            $table->foreignId('picked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('packed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('shipped_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('outbound_items', function (Blueprint $table) {
            $table->id(); $table->foreignId('outbound_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bin_id')->nullable()->constrained()->nullOnDelete();
            $table->string('batch_number')->nullable(); $table->decimal('quantity',12,3);
            $table->decimal('picked_quantity',12,3)->default(0);
            $table->decimal('unit_price',12,2)->nullable(); $table->timestamps();
        });

        Schema::create('planograms', function (Blueprint $table) {
            $table->id(); $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->string('name'); $table->text('description')->nullable();
            $table->integer('canvas_width')->default(1200); $table->integer('canvas_height')->default(800);
            $table->jsonb('canvas_data')->nullable(); $table->integer('version')->default(1);
            $table->boolean('is_published')->default(false); $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete(); $table->timestamps();
        });

        Schema::create('planogram_items', function (Blueprint $table) {
            $table->id(); $table->foreignId('planogram_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('bin_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('rack_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('x',10,2)->default(0); $table->decimal('y',10,2)->default(0);
            $table->decimal('width',10,2)->default(60); $table->decimal('height',10,2)->default(60);
            $table->decimal('rotation',5,2)->default(0); $table->integer('layer')->default(0);
            $table->jsonb('config')->nullable(); $table->boolean('is_facings')->default(false);
            $table->integer('min_facings')->default(1); $table->timestamps();
        });

        // Seed default roles
        DB::table('roles')->insert([
            ['name'=>'admin','display_name'=>'Administrator','description'=>'Full system access','is_active'=>true,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'warehouse_manager','display_name'=>'Warehouse Manager','description'=>'Manage warehouse ops','is_active'=>true,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'operator','display_name'=>'Operator','description'=>'Warehouse operator','is_active'=>true,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'viewer','display_name'=>'Viewer','description'=>'Read-only access','is_active'=>true,'created_at'=>now(),'updated_at'=>now()],
        ]);

        // Seed default admin
        DB::table('users')->insert([
            'name'=>'Administrator','email'=>'admin@wms.local','password'=>bcrypt('password'),
            'role_id'=>1,'is_active'=>true,'created_at'=>now(),'updated_at'=>now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('planogram_items');
        Schema::dropIfExists('planograms');
        Schema::dropIfExists('outbound_items');
        Schema::dropIfExists('outbounds');
        Schema::dropIfExists('inbound_items');
        Schema::dropIfExists('inbounds');
        Schema::dropIfExists('stocks');
        Schema::dropIfExists('bins');
        Schema::dropIfExists('racks');
        Schema::dropIfExists('products');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('user_permissions');
        Schema::dropIfExists('user_warehouse');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
