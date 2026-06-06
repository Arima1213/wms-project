import os

migrations_dir = "C:/Users/ASUS/Downloads/docker-setup/wms-project/backend/database/migrations"
os.makedirs(migrations_dir, exist_ok=True)

def write_migration(filename, content):
    path = os.path.join(migrations_dir, filename)
    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)
    print(f"Created: {filename}")

write_migration("2025_06_05_000001_create_warehouses_table.php", r"""<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("warehouses", function (Blueprint $table) {
            $table->id();
            $table->string("code", 20)->unique();
            $table->string("name");
            $table->text("address")->nullable();
            $table->decimal("latitude", 10, 8)->nullable();
            $table->decimal("longitude", 11, 8)->nullable();
            $table->decimal("capacity_sqm", 12, 2)->default(0);
            $table->decimal("used_capacity_sqm", 12, 2)->default(0);
            $table->enum("type", ["reguler", "cold_storage", "bonded", "konsinyasi"])->default("reguler");
            $table->string("pic_name")->nullable();
            $table->string("pic_phone")->nullable();
            $table->json("operational_hours")->nullable();
            $table->boolean("is_active")->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("warehouses");
    }
};
""")

write_migration("2025_06_05_000002_create_warehouse_zones_table.php", r"""<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("warehouse_zones", function (Blueprint $table) {
            $table->id();
            $table->foreignId("warehouse_id")->constrained()->cascadeOnDelete();
            $table->string("code", 10);
            $table->string("name");
            $table->string("color", 7)->default("#3B82F6");
            $table->decimal("min_temperature", 5, 2)->nullable();
            $table->decimal("max_temperature", 5, 2)->nullable();
            $table->integer("min_humidity")->nullable();
            $table->integer("max_humidity")->nullable();
            $table->json("allowed_categories")->nullable();
            $table->integer("sort_order")->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("warehouse_zones");
    }
};
""")

write_migration("2025_06_05_000003_create_racks_table.php", r"""<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("racks", function (Blueprint $table) {
            $table->id();
            $table->foreignId("zone_id")->constrained("warehouse_zones")->cascadeOnDelete();
            $table->string("code", 20);
            $table->string("name")->nullable();
            $table->decimal("pos_x", 8, 2)->default(0);
            $table->decimal("pos_y", 8, 2)->default(0);
            $table->decimal("width", 8, 2)->default(4);
            $table->decimal("depth", 8, 2)->default(2);
            $table->decimal("rotation", 5, 2)->default(0);
            $table->integer("levels")->default(3);
            $table->integer("columns_per_level")->default(4);
            $table->decimal("max_weight_per_kg", 8, 2)->default(500);
            $table->boolean("is_active")->default(true);
            $table->timestamps();
            $table->unique(["zone_id", "code"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("racks");
    }
};
""")

write_migration("2025_06_05_000004_create_rack_levels_table.php", r"""<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("rack_levels", function (Blueprint $table) {
            $table->id();
            $table->foreignId("rack_id")->constrained()->cascadeOnDelete();
            $table->tinyInteger("level_number");
            $table->decimal("height_cm", 6, 2)->default(200);
            $table->decimal("max_weight_kg", 8, 2)->default(200);
            $table->integer("sort_order")->default(0);
            $table->timestamps();
            $table->unique(["rack_id", "level_number"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("rack_levels");
    }
};
""")

write_migration("2025_06_05_000005_create_rack_slots_table.php", r"""<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("rack_slots", function (Blueprint $table) {
            $table->id();
            $table->foreignId("rack_level_id")->constrained()->cascadeOnDelete();
            $table->smallInteger("slot_number");
            $table->string("slot_code", 30);
            $table->decimal("max_weight_kg", 8, 2)->default(100);
            $table->decimal("max_height_cm", 6, 2)->default(50);
            $table->decimal("max_width_cm", 6, 2)->default(60);
            $table->decimal("max_depth_cm", 6, 2)->default(60);
            $table->enum("slot_type", ["fixed", "floating"])->default("floating");
            $table->foreignId("fixed_product_id")->nullable()->constrained("products")->nullOnDelete();
            $table->enum("status", ["empty", "partial", "full", "reserved"])->default("empty");
            $table->timestamp("reserved_until")->nullable();
            $table->string("reserved_for")->nullable();
            $table->boolean("is_active")->default(true);
            $table->timestamps();
            $table->unique(["rack_level_id", "slot_number"]);
            $table->unique(["slot_code"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("rack_slots");
    }
};
""")

write_migration("2025_06_05_000006_create_product_categories_table.php", r"""<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("product_categories", function (Blueprint $table) {
            $table->id();
            $table->foreignId("parent_id")->nullable()->constrained("product_categories")->nullOnDelete();
            $table->string("code", 20)->unique();
            $table->string("name");
            $table->text("description")->nullable();
            $table->string("icon")->nullable();
            $table->integer("sort_order")->default(0);
            $table->boolean("is_active")->default(true);
            $table->timestamps();
            $table->index("parent_id");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("product_categories");
    }
};
""")

write_migration("2025_06_05_000007_create_products_table.php", r"""<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("products", function (Blueprint $table) {
            $table->id();
            $table->string("sku", 50)->unique();
            $table->string("barcode", 50)->nullable();
            $table->string("name");
            $table->text("description")->nullable();
            $table->foreignId("category_id")->nullable()->constrained("product_categories")->nullOnDelete();
            $table->decimal("length_cm", 6, 2)->default(0);
            $table->decimal("width_cm", 6, 2)->default(0);
            $table->decimal("height_cm", 6, 2)->default(0);
            $table->decimal("weight_kg", 8, 3)->default(0);
            $table->decimal("min_stock", 12, 2)->default(0);
            $table->decimal("max_stock", 12, 2)->default(0);
            $table->decimal("reorder_point", 12, 2)->default(0);
            $table->decimal("cost_price", 12, 2)->default(0);
            $table->decimal("selling_price", 12, 2)->default(0);
            $table->string("base_uom", 10)->default("pcs");
            $table->decimal("weight_per_pcs_kg", 8, 4)->default(0);
            $table->boolean("track_expiry")->default(false);
            $table->integer("shelf_life_days")->nullable();
            $table->string("image_url")->nullable();
            $table->json("documents")->nullable();
            $table->boolean("is_active")->default(true);
            $table->boolean("requires_cold_storage")->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->index(["category_id", "is_active"]);
            $table->index("sku");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("products");
    }
};
""")

write_migration("2025_06_05_000008_create_product_barcodes_table.php", r"""<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("product_barcodes", function (Blueprint $table) {
            $table->id();
            $table->foreignId("product_id")->constrained()->cascadeOnDelete();
            $table->string("barcode", 50);
            $table->enum("type", ["ean", "upc", "internal", "qr"])->default("internal");
            $table->boolean("is_primary")->default(false);
            $table->timestamps();
            $table->unique(["barcode", "type"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("product_barcodes");
    }
};
""")

write_migration("2025_06_05_000009_create_uom_conversions_table.php", r"""<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("uom_conversions", function (Blueprint $table) {
            $table->id();
            $table->foreignId("product_id")->constrained()->cascadeOnDelete();
            $table->string("from_uom", 10);
            $table->string("to_uom", 10);
            $table->decimal("conversion_factor", 10, 4);
            $table->boolean("is_active")->default(true);
            $table->timestamps();
            $table->unique(["product_id", "from_uom", "to_uom"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("uom_conversions");
    }
};
""")

write_migration("2025_06_05_000010_create_stock_items_table.php", r"""<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("stock_items", function (Blueprint $table) {
            $table->id();
            $table->foreignId("slot_id")->constrained("rack_slots")->cascadeOnDelete();
            $table->foreignId("product_id")->constrained()->cascadeOnDelete();
            $table->string("batch_number", 50)->nullable();
            $table->date("manufacture_date")->nullable();
            $table->date("expiry_date")->nullable();
            $table->decimal("quantity", 12, 4)->default(0);
            $table->decimal("reserved_quantity", 12, 4)->default(0);
            $table->decimal("avg_cost_price", 12, 2)->default(0);
            $table->string("lot_number", 50)->nullable();
            $table->text("notes")->nullable();
            $table->timestamps();
            $table->index(["slot_id", "product_id"]);
            $table->index("batch_number");
            $table->index("expiry_date");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("stock_items");
    }
};
""")

write_migration("2025_06_05_000011_create_inventory_transactions_table.php", r"""<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("inventory_transactions", function (Blueprint $table) {
            $table->id();
            $table->string("transaction_number", 30)->unique();
            $table->enum("type", ["GR","GI","TR","LT","SO","ADJ+","ADJ-","RS","RC"])->default("GR");
            $table->foreignId("warehouse_id")->constrained()->cascadeOnDelete();
            $table->string("reference_type")->nullable();
            $table->unsignedBigInteger("reference_id")->nullable();
            $table->foreignId("user_id")->constrained()->cascadeOnDelete();
            $table->enum("status", ["draft","pending","approved","rejected","completed","cancelled"])->default("draft");
            $table->enum("priority", ["low","normal","high","urgent"])->default("normal");
            $table->text("notes")->nullable();
            $table->json("metadata")->nullable();
            $table->timestamp("approved_at")->nullable();
            $table->foreignId("approved_by")->nullable()->constrained("users")->nullOnDelete();
            $table->timestamp("completed_at")->nullable();
            $table->timestamps();
            $table->index(["warehouse_id", "type", "status"]);
            $table->index("transaction_number");
            $table->index("created_at");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("inventory_transactions");
    }
};
""")

write_migration("2025_06_05_000012_create_transaction_items_table.php", r"""<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("transaction_items", function (Blueprint $table) {
            $table->id();
            $table->foreignId("transaction_id")->constrained()->cascadeOnDelete();
            $table->foreignId("product_id")->constrained()->cascadeOnDelete();
            $table->foreignId("from_slot_id")->nullable()->constrained("rack_slots")->nullOnDelete();
            $table->foreignId("to_slot_id")->nullable()->constrained("rack_slots")->nullOnDelete();
            $table->string("batch_number", 50)->nullable();
            $table->date("expiry_date")->nullable();
            $table->decimal("quantity", 12, 4)->default(0);
            $table->decimal("uom_quantity", 12, 4)->default(0);
            $table->string("uom")->default("pcs");
            $table->decimal("cost_price", 12, 2)->default(0);
            $table->string("lot_number", 50)->nullable();
            $table->text("notes")->nullable();
            $table->timestamps();
            $table->index(["transaction_id", "product_id"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("transaction_items");
    }
};
""")

write_migration("2025_06_05_000013_create_planograms_table.php", r"""<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("planograms", function (Blueprint $table) {
            $table->id();
            $table->foreignId("warehouse_id")->constrained()->cascadeOnDelete();
            $table->string("name");
            $table->text("description")->nullable();
            $table->json("canvas_data");
            $table->json("metadata")->nullable();
            $table->bigInteger("version")->default(1);
            $table->foreignId("created_by")->constrained()->cascadeOnDelete();
            $table->foreignId("approved_by")->nullable()->constrained("users")->nullOnDelete();
            $table->timestamp("approved_at")->nullable();
            $table->enum("status", ["draft","active","archived"])->default("draft");
            $table->timestamps();
            $table->index(["warehouse_id", "status"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("planograms");
    }
};
""")

write_migration("2025_06_05_000014_create_users_table.php", r"""<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("users", function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("username", 50)->unique();
            $table->string("email")->unique();
            $table->timestamp("email_verified_at")->nullable();
            $table->string("password");
            $table->string("phone", 20)->nullable();
            $table->boolean("is_active")->default(true);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("users");
    }
};
""")

write_migration("2025_06_05_000015_create_audit_logs_table.php", r"""<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("audit_logs", function (Blueprint $table) {
            $table->id();
            $table->foreignId("user_id")->nullable()->constrained()->nullOnDelete();
            $table->string("user_type")->nullable();
            $table->string("action", 50);
            $table->string("entity_type", 100)->nullable();
            $table->unsignedBigInteger("entity_id")->nullable();
            $table->json("old_values")->nullable();
            $table->json("new_values")->nullable();
            $table->string("ip_address", 45)->nullable();
            $table->string("user_agent")->nullable();
            $table->timestamp("created_at")->useCurrent();
            $table->index(["entity_type", "entity_id"]);
            $table->index("user_id");
            $table->index("created_at");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("audit_logs");
    }
};
""")

write_migration("2025_06_05_000016_create_failed_jobs_table.php", r"""<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("failed_jobs", function (Blueprint $table) {
            $table->id();
            $table->string("uuid")->unique();
            $table->text("connection");
            $table->text("queue");
            $table->longText("payload");
            $table->longText("exception");
            $table->timestamp("failed_at")->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("failed_jobs");
    }
};
""")

write_migration("2025_06_05_000017_create_sessions_table.php", r"""<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("sessions", function (Blueprint $table) {
            $table->string("id")->primary();
            $table->foreignId("user_id")->nullable()->index();
            $table->string("ip_address", 45)->nullable();
            $table->text("user_agent")->nullable();
            $table->longText("payload");
            $table->integer("last_activity")->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("sessions");
    }
};
""")

print("All 17 migrations created successfully!")
