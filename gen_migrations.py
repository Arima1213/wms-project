import os

base = r'C:\Users\ASUS\Downloads\docker-setup\wms-project\backend'

migrations = {}

# ── Users & Auth ──────────────────────────────────────────────────────────────
migrations['001_create_users_table.php'] = '''<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->string('avatar')->nullable();
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
'''

migrations['002_create_warehouses_table.php'] = '''<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('capacity_m2', 12, 2)->nullable();
            $table->enum('type', ['regular', 'cold_storage', 'bonded', 'consignment'])->default('regular');
            $table->string('pic_name')->nullable();
            $table->string('pic_phone')->nullable();
            $table->string('pic_email')->nullable();
            $table->json('operating_hours')->nullable(); // {"monday": "08:00-18:00", ...}
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
'''

migrations['003_create_warehouse_zones_table.php'] = '''<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->string('code', 10); // A, B, C, COLD
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('zone_type', ['fast_moving', 'slow_moving', 'heavy', 'cold', 'hazmat', 'general'])->default('general');
            $table->decimal('min_temp', 5, 2)->nullable();
            $table->decimal('max_temp', 5, 2)->nullable();
            $table->decimal('min_humidity', 5, 2)->nullable();
            $table->decimal('max_humidity', 5, 2)->nullable();
            $table->json('allowed_categories')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_zones');
    }
};
'''

migrations['004_create_racks_table.php'] = '''<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('racks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained('warehouse_zones')->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name')->nullable();
            $table->integer('pos_x')->default(0);
            $table->integer('pos_y')->default(0);
            $table->decimal('width_cm', 8, 2)->default(100);
            $table->decimal('depth_cm', 8, 2)->default(50);
            $table->decimal('height_cm', 8, 2)->default(200);
            $table->integer('levels')->default(3);
            $table->integer('columns_per_level')->default(4);
            $table->decimal('max_weight_kg', 8, 2)->default(500);
            $table->enum('orientation', ['horizontal', 'vertical'])->default('horizontal');
            $table->timestamps();
            $table->unique(['zone_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('racks');
    }
};
'''

migrations['005_create_rack_levels_table.php'] = '''<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rack_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rack_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('level_number'); // 1, 2, 3...
            $table->decimal('height_cm', 6, 2)->default(30);
            $table->decimal('max_weight_kg', 8, 2)->default(100);
            $table->timestamps();
            $table->unique(['rack_id', 'level_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rack_levels');
    }
};
'''

migrations['006_create_rack_slots_table.php'] = '''<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

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
'''

migrations['007_create_product_categories_table.php'] = '''<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_categories');
    }
};
'''

migrations['008_create_products_table.php'] = '''<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

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
'''

migrations['009_create_product_barcodes_table.php'] = '''<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_barcodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('barcode', 50)->unique();
            $table->string('type', 20)->default('EAN13'); // EAN13, QR, CODE128
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_barcodes');
    }
};
'''

migrations['010_create_uom_conversions_table.php'] = '''<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uom_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('from_unit', 20);
            $table->string('to_unit', 20);
            $table->decimal('conversion_factor', 10, 4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uom_conversions');
    }
};
'''

migrations['011_create_inventory_table.php'] = '''<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rack_slot_id')->nullable()->constrained('rack_slots')->nullOnDelete();
            $table->string('batch_number', 50)->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('quantity', 12, 3)->default(0);
            $table->decimal('reserved_quantity', 12, 3)->default(0);
            $table->decimal('available_quantity', 12, 3)->default(0);
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->timestamps();
            $table->unique(['warehouse_id', 'product_id', 'rack_slot_id', 'batch_number'], 'inv_warehouse_product_slot_batch');
            $table->index(['warehouse_id', 'product_id']);
 });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};
'''

migrations['012_create_inventory_transactions_table.php'] = '''<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number', 50)->unique();
            $table->enum('type', ['GR', 'GI', 'TR', 'LT', 'SO', 'ADJ+', 'ADJ-', 'RS', 'RC']);
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rack_slot_id')->nullable()->constrained('rack_slots')->nullOnDelete();
            $table->string('batch_number', 50)->nullable();
            $table->decimal('quantity', 12, 3);
            $table->decimal('before_quantity', 12, 3)->default(0);
            $table->decimal('after_quantity', 12, 3)->default(0);
            $table->enum('direction', ['in', 'out', 'transfer'])->default('in');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reference_type', 50)->nullable(); // Inbound, Outbound, Transfer
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['warehouse_id', 'created_at']);
            $table->index(['product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
'''

migrations['013_create_inbounds_table.php'] = '''<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inbounds', function (Blueprint $table) {
            $table->id();
            $table->string('inbound_number', 50)->unique();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['draft', 'approved', 'partial_received', 'received', 'cancelled'])->default('draft');
            $table->enum('source_type', ['purchase_order', 'return_supplier', 'return_customer', 'transfer_in', 'other'])->default('purchase_order');
            $table->string('source_reference', 100)->nullable();
            $table->date('expected_date')->nullable();
            $table->date('received_date')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inbounds');
    }
};
'''

migrations['014_create_inbound_items_table.php'] = '''<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inbound_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inbound_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('ordered_quantity', 12, 3);
            $table->decimal('received_quantity', 12, 3)->default(0);
            $table->string('batch_number', 50)->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->string('target_slot_code', 20)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inbound_items');
    }
};
'''

migrations['015_create_outbounds_table.php'] = '''<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbounds', function (Blueprint $table) {
            $table->id();
            $table->string('outbound_number', 50)->unique();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['draft', 'picking', 'picked', 'shipped', 'delivered', 'cancelled'])->default('draft');
            $table->enum('destination_type', ['sales_order', 'production', 'transfer_out', 'sample', 'other'])->default('sales_order');
            $table->string('destination_reference', 100)->nullable();
            $table->string('customer_name', 200)->nullable();
            $table->text('shipping_address')->nullable();
            $table->date('expected_date')->nullable();
            $table->date('shipped_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbounds');
    }
};
'''

migrations['016_create_outbound_items_table.php'] = '''<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbound_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outbound_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('ordered_quantity', 12, 3);
            $table->decimal('picked_quantity', 12, 3)->default(0);
            $table->foreignId('rack_slot_id')->nullable()->constrained('rack_slots')->nullOnDelete();
            $table->string('batch_number', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbound_items');
    }
};
'''

migrations['017_create_transfers_table.php'] = '''<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number', 50)->unique();
            $table->foreignId('source_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('dest_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'in_transit', 'received', 'rejected', 'cancelled'])->default('draft');
            $table->text('reason')->nullable();
            $table->date('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('received_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
'''

migrations['018_create_transfer_items_table.php'] = '''<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->string('batch_number', 50)->nullable();
            $table->foreignId('source_slot_id')->nullable()->constrained('rack_slots')->nullOnDelete();
            $table->foreignId('dest_slot_id')->nullable()->constrained('rack_slots')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_items');
    }
};
'''

migrations['019_create_stock_opnames_table.php'] = '''<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->string('opname_number', 50)->unique();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['draft', 'in_progress', 'submitted', 'approved', 'cancelled'])->default('draft');
            $table->enum('opname_type', ['full', 'cycle_count'])->default('full');
            $table->date('opname_date')->nullable();
            $table->date('submitted_at')->nullable();
            $table->date('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opnames');
    }
};
'''

migrations['020_create_stock_opname_items_table.php'] = '''<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_opname_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_opname_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rack_slot_id')->nullable()->constrained('rack_slots')->nullOnDelete();
            $table->string('batch_number', 50)->nullable();
            $table->decimal('system_quantity', 12, 3);
            $table->decimal('counted_quantity', 12, 3)->nullable();
            $table->decimal('variance', 12, 3)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opname_items');
    }
};
'''

migrations['021_create_planograms_table.php'] = '''<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

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
'''

migrations['022_create_planogram_snapshots_table.php'] = '''<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planogram_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planogram_id')->constrained()->cascadeOnDelete();
            $table->string('version', 20);
            $table->json('canvas_data');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->text('change_summary')->nullable();
            $table->timestamp('created_at');
 });
    }

    public function down(): void
    {
        Schema::dropIfExists('planogram_snapshots');
    }
};
'''

migrations['023_create_audit_logs_table.php'] = '''<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event', 50);
            $table->string('entity_type', 50);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at');
 $table->index(['entity_type', 'entity_id']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
'''

migrations['024_create_documents_table.php'] = '''<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('original_name');
            $table->string('type', 50);
            $table->bigInteger('size');
            $table->string('path');
            $table->string('disk')->default('minio');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
'''

migrations['025_create_permission_tables.php'] = '''<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('guard_name')->default('web');
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('guard_name')->default('web');
            $table->timestamps();
        });

        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->primary(['permission_id', 'role_id']);
        });

        Schema::create('user_has_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'role_id']);
        });

        Schema::create('user_has_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'permission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_has_permissions');
        Schema::dropIfExists('user_has_roles');
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
    }
};
'''

migrations_dir = f'{base}/database/migrations'
os.makedirs(migrations_dir, exist_ok=True)
for filename, content in migrations.items():
    path = f'{migrations_dir}/{filename}'
    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)
    print(f'Created migration: {filename}')

print(f'All {len(migrations)} migrations created!')
