import os

BASE = 'C:/Users/ASUS/Downloads/docker-setup/wms-project'

# --- Migration ---
mig = BASE + '/backend/database/migrations/2024_01_01_000001_create_initial_schema.php'
os.makedirs(os.path.dirname(mig), exist_ok=True)

migration_content = """<?php
use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;
use Illuminate\\Support\\Facades\\DB;

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
"""

with open(mig, 'w') as f:
    f.write(migration_content)
print(f"Migration written: {mig}")

# --- Laravel Config files ---
config_dir = BASE + '/backend/config'
os.makedirs(config_dir, exist_ok=True)

configs = {
    'app.php': """<?php
return [
    'name' => env('APP_NAME', 'WMS'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => 'Asia/Makassar',
    'locale' => 'id',
    'fallback_locale' => 'en',
    'faker_locale' => 'id_ID',
    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',
    'maintenance' => ['driver' => 'file'],
    'providers' => [],
    'aliases' => [],
];
""",

    'database.php': """<?php
return [
    'default' => env('DB_CONNECTION', 'pgsql'),
    'connections' => [
        'pgsql' => [
            'driver' => 'pgsql', 'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'), 'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'wms'), 'username' => env('DB_USERNAME', 'wms_user'),
            'password' => env('DB_PASSWORD', ''), 'charset' => 'utf8',
            'prefix' => '', 'prefix_indexes' => true, 'search_path' => 'public',
            'sslmode' => 'prefer',
        ],
    ],
    'migrations' => ['table' => 'migrations', 'update_date_on_publish' => true],
    'redis' => [
        'client' => env('REDIS_CLIENT', 'phpredis'),
        'options' => ['cluster' => env('REDIS_CLUSTER', 'redis'), 'prefix' => env('REDIS_PREFIX', 'wms_')],
        'default' => ['url' => env('REDIS_URL'), 'host' => env('REDIS_HOST', '127.0.0.1'), 'password' => env('REDIS_PASSWORD'), 'port' => env('REDIS_PORT', '6379'), 'database' => env('REDIS_DB', '0')],
        'cache' => ['url' => env('REDIS_URL'), 'host' => env('REDIS_HOST', '127.0.0.1'), 'password' => env('REDIS_PASSWORD'), 'port' => env('REDIS_PORT', '6379'), 'database' => env('REDIS_CACHE_DB', '1')],
    ],
];
""",

    'cache.php': """<?php
return [
    'default' => env('CACHE_DRIVER', 'redis'),
    'stores' => [
        'redis' => ['driver' => 'redis', 'connection' => 'cache', 'lock_connection' => 'default'],
        'file' => ['driver' => 'file', 'path' => storage_path('framework/cache/data'), 'lock_path' => storage_path('framework/cache/data')],
        'array' => ['driver' => 'array', 'serialize' => false],
    ],
    'prefix' => env('CACHE_PREFIX', 'wms_cache'),
];
""",

    'filesystems.php': """<?php
return [
    'default' => env('FILESYSTEM_DISK', 'local'),
    'disks' => [
        'local' => ['driver' => 'local', 'root' => storage_path('app'), 'throw' => false],
        'public' => ['driver' => 'local', 'root' => storage_path('app/public'), 'url' => env('APP_URL').'/storage', 'visibility' => 'public', 'throw' => false],
        'minio' => [
            'driver' => 's3',
            'key' => env('MINIO_ACCESS_KEY'), 'secret' => env('MINIO_SECRET_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'bucket' => env('MINIO_BUCKET'), 'url' => env('MINIO_URL'),
            'endpoint' => env('MINIO_ENDPOINT'), 'use_path_style_endpoint' => true,
            'throw' => false,
        ],
    ],
];
""",

    'services.php': """<?php
return [
    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST', 'http://localhost:7700'),
        'key' => env('MEILISEARCH_KEY', ''),
    ],
];
""",

    'cors.php': """<?php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
""",

    'session.php': """<?php
return [
    'driver' => env('SESSION_DRIVER', 'redis'),
    'lifetime' => env('SESSION_LIFETIME', 120),
    'expire_on_close' => false,
    'encrypt' => false,
    'files' => storage_path('framework/sessions'),
    'connection' => env('SESSION_CONNECTION'),
    'table' => 'sessions',
    'store' => env('SESSION_STORE'),
    'lottery' => [2, 100],
    'cookie' => env('SESSION_COOKIE', 'wms_session'),
    'path' => '/',
    'domain' => env('SESSION_DOMAIN'),
    'secure' => env('SESSION_SECURE_COOKIE'),
    'http_only' => true,
    'same_site' => 'lax',
    'partitioned' => false,
];
""",

    'queue.php': """<?php
return [
    'default' => env('QUEUE_CONNECTION', 'redis'),
    'connections' => [
        'sync' => ['driver' => 'sync'],
        'redis' => ['driver' => 'redis', 'connection' => 'default', 'queue' => env('REDIS_QUEUE', 'wms'), 'retry_after' => 90, 'block_for' => null],
    ],
    'batching' => ['database' => env('DB_CONNECTION'), 'table' => 'job_batches'],
    'failed' => ['driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'), 'database' => env('DB_CONNECTION'), 'table' => 'failed_jobs'],
];
""",
}

for fname, content in configs.items():
    path = os.path.join(config_dir, fname)
    with open(path, 'w') as f:
        f.write(content)
    print(f"Config: {fname}")

# --- Makefile ---
makefile = BASE + '/Makefile'
makefile_content = """.PHONY: up down build logs shell migrate seed restart clean

# Default target
all: up

up:
\t@echo "Starting WMS containers..."
\tdocker compose up -d
\t@echo "Waiting for services..."
\t@sleep 5
\t@echo "WMS is running! API: http://localhost:8000 | Frontend: http://localhost:3000 | MinIO: http://localhost:9001"

down:
\t@echo "Stopping WMS containers..."
\tdocker compose down

build:
\t@echo "Building WMS containers..."
\tdocker compose build --no-cache

restart:
\tdocker compose restart

logs:
\tdocker compose logs -f

logs-app:
\tdocker compose logs -f app

shell-app:
\tdocker compose exec app bash

shell-db:
\tdocker compose exec db psql -U wms_user -d wms

migrate:
\tdocker compose exec app php artisan migrate --force

migrate-rollback:
\tdocker compose exec app php artisan migrate:rollback --force

seed:
\tdocker compose exec app php artisan db:seed --force

fresh:
\tdocker compose exec app php artisan migrate:fresh --seed

key:
\tdocker compose exec app php artisan key:generate

clear-cache:
\tdocker compose exec app php artisan config:clear
\tdocker compose exec app php artisan cache:clear

queue:
\tdocker compose exec app php artisan queue:work --tries=3 --sleep=3

clean:
\tdocker compose down -v --remove-orphans
\trm -rf frontend/dist

help:
\t@echo "WMS Multi-Gudang - Available Commands:"
\t@echo "  make up            - Start all containers"
\t@echo "  make down          - Stop all containers"
\t@echo "  make build         - Rebuild containers (no cache)"
\t@echo "  make restart       - Restart all containers"
\t@echo "  make logs          - View all logs"
\t@echo "  make logs-app      - View app logs only"
\t@echo "  make shell-app     - Bash shell into app container"
\t@echo "  make shell-db      - PostgreSQL shell"
\t@echo "  make migrate       - Run migrations"
\t@echo "  make migrate-rollback - Rollback last migration"
\t@echo "  make seed          - Seed database"
\t@echo "  make fresh         - Fresh migrate + seed"
\t@echo "  make key           - Generate app key"
\t@echo "  make clear-cache   - Clear all caches"
\t@echo "  make queue         - Start queue worker"
\t@echo "  make clean         - Remove containers + volumes"
"""

with open(makefile, 'w') as f:
    f.write(makefile_content)
print("Makefile created")

print("\\n=== Backend scaffold complete ===")