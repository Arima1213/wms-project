import os

base = r'C:\Users\ASUS\Downloads\docker-setup\wms-project\backend'

# ── Models ────────────────────────────────────────────────────────────────────
models = {
    'User.php': '''<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Relations\\HasMany;
use Illuminate\\Database\\Eloquent\\SoftDeletes;
use Illuminate\\Foundation\\Auth\\User as Authenticatable;
use Illuminate\\Notifications\\Notifiable;
use Laravel\\Sanctum\\HasApiTokens;
use Spatie\\Permission\\Traits\\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles;

    protected $fillable = ['name', 'email', 'password', 'phone', 'avatar', 'is_active'];
    protected $hidden = ['password', 'remember_token'];
    protected $casts = ['is_active' => 'boolean', 'email_verified_at' => 'datetime'];

    public function inbounds(): HasMany { return $this->hasMany(Inbound::class); }
    public function outbounds(): HasMany { return $this->hasMany(Outbound::class); }
    public function transfers(): HasMany { return $this->hasMany(Transfer::class); }
    public function stockOpnames(): HasMany { return $this->hasMany(StockOpname::class); }
    public function auditLogs(): HasMany { return $this->hasMany(AuditLog::class); }
}
''',

    'Warehouse.php': '''<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\HasMany;
use Illuminate\\Database\\Eloquent\\Relations\\HasOne;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'address', 'city', 'latitude', 'longitude',
        'capacity_m2', 'type', 'pic_name', 'pic_phone', 'pic_email',
        'operating_hours', 'is_active',
    ];

    protected $casts = [
        'latitude' => 'decimal:7', 'longitude' => 'decimal:7',
        'capacity_m2' => 'decimal:2', 'operating_hours' => 'array', 'is_active' => 'boolean',
    ];

    public function zones(): HasMany { return $this->hasMany(WarehouseZone::class); }
    public function planogram(): HasOne { return $this->hasOne(Planogram::class); }
    public function inventory(): HasMany { return $this->hasMany(Inventory::class); }
    public function inbounds(): HasMany { return $this->hasMany(Inbound::class); }
    public function outbounds(): HasMany { return $this->hasMany(Outbound::class); }
}
''',

    'WarehouseZone.php': '''<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
use Illuminate\\Database\\Eloquent\\Relations\\HasMany;

class WarehouseZone extends Model
{
    use HasFactory;
    protected $table = 'warehouse_zones';

    protected $fillable = [
        'warehouse_id', 'code', 'name', 'description', 'zone_type',
        'min_temp', 'max_temp', 'min_humidity', 'max_humidity',
        'allowed_categories', 'is_active',
    ];

    protected $casts = [
        'allowed_categories' => 'array', 'is_active' => 'boolean',
        'min_temp' => 'decimal:2', 'max_temp' => 'decimal:2',
        'min_humidity' => 'decimal:2', 'max_humidity' => 'decimal:2',
    ];

    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function racks(): HasMany { return $this->hasMany(Rack::class); }
}
''',

    'Rack.php': '''<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
use Illuminate\\Database\\Eloquent\\Relations\\HasMany;
use Illuminate\\Database\\Eloquent\\Relations\\HasManyThrough;

class Rack extends Model
{
    use HasFactory;

    protected $fillable = [
        'zone_id', 'code', 'name', 'pos_x', 'pos_y',
        'width_cm', 'depth_cm', 'height_cm', 'levels',
        'columns_per_level', 'max_weight_kg', 'orientation',
    ];

    protected $casts = [
        'pos_x' => 'integer', 'pos_y' => 'integer',
        'width_cm' => 'decimal:2', 'depth_cm' => 'decimal:2', 'height_cm' => 'decimal:2',
        'max_weight_kg' => 'decimal:2', 'levels' => 'integer', 'columns_per_level' => 'integer',
    ];

    public function zone(): BelongsTo { return $this->belongsTo(WarehouseZone::class, 'zone_id'); }
    public function levels(): HasMany { return $this->hasMany(RackLevel::class); }
    public function slots(): HasManyThrough { return $this->hasManyThrough(RackSlot::class, RackLevel::class); }
}
''',

    'RackLevel.php': '''<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
use Illuminate\\Database\\Eloquent\\Relations\\HasMany;

class RackLevel extends Model
{
    use HasFactory;

    protected $fillable = ['rack_id', 'level_number', 'height_cm', 'max_weight_kg'];
    protected $casts = [
        'level_number' => 'integer', 'height_cm' => 'decimal:2', 'max_weight_kg' => 'decimal:2',
    ];

    public function rack(): BelongsTo { return $this->belongsTo(Rack::class); }
    public function slots(): HasMany { return $this->hasMany(RackSlot::class); }
}
''',

    'RackSlot.php': '''<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
use Illuminate\\Database\\Eloquent\\Relations\\HasMany;

class RackSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'rack_level_id', 'slot_code', 'column_number',
        'width_cm', 'depth_cm', 'height_cm', 'max_weight_kg',
        'slot_type', 'status',
    ];

    protected $casts = [
        'column_number' => 'integer',
        'width_cm' => 'decimal:2', 'depth_cm' => 'decimal:2', 'height_cm' => 'decimal:2',
        'max_weight_kg' => 'decimal:2',
    ];

    public function rackLevel(): BelongsTo { return $this->belongsTo(RackLevel::class); }
    public function inventory(): HasMany { return $this->hasMany(Inventory::class); }

    public function getRackAttribute(): ?Rack
    {
        return $this->rackLevel?->rack;
    }
}
''',

    'ProductCategory.php': '''<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
use Illuminate\\Database\\Eloquent\\Relations\\HasMany;

class ProductCategory extends Model
{
    use HasFactory;

    protected $fillable = ['parent_id', 'code', 'name', 'description', 'icon', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function parent(): BelongsTo { return $this->belongsTo(ProductCategory::class, 'parent_id'); }
    public function children(): HasMany { return $this->hasMany(ProductCategory::class, 'parent_id'); }
    public function products(): HasMany { return $this->hasMany(Product::class); }
}
''',

    'Product.php': '''<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
use Illuminate\\Database\\Eloquent\\Relations\\HasMany;
use Illuminate\\Database\\Eloquent\\SoftDeletes;
use Laravel\\Scout\\Searchable;

class Product extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    protected $fillable = [
        'sku', 'barcode', 'name', 'description', 'category_id', 'unit',
        'length_cm', 'width_cm', 'height_cm', 'weight_kg',
        'min_stock', 'max_stock', 'safety_stock', 'reorder_point',
        'purchase_price', 'selling_price', 'image', 'is_active',
    ];

    protected $casts = [
        'length_cm' => 'decimal:3', 'width_cm' => 'decimal:3', 'height_cm' => 'decimal:3',
        'weight_kg' => 'decimal:3', 'min_stock' => 'decimal:3', 'max_stock' => 'decimal:3',
        'safety_stock' => 'decimal:3', 'reorder_point' => 'decimal:3',
        'purchase_price' => 'decimal:2', 'selling_price' => 'decimal:2', 'is_active' => 'boolean',
    ];

    public function category(): BelongsTo { return $this->belongsTo(ProductCategory::class); }
    public function barcodes(): HasMany { return $this->hasMany(ProductBarcode::class); }
    public function uomConversions(): HasMany { return $this->hasMany(UomConversion::class); }
    public function inventory(): HasMany { return $this->hasMany(Inventory::class); }

    public function searchableAs(): string { return 'products'; }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'name' => $this->name,
            'category' => $this->category?->name,
        ];
    }
}
''',

    'ProductBarcode.php': '''<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

class ProductBarcode extends Model
{
    use HasFactory;
    protected $fillable = ['product_id', 'barcode', 'type', 'is_primary'];
    protected $casts = ['is_primary' => 'boolean'];
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
''',

    'UomConversion.php': '''<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

class UomConversion extends Model
{
    use HasFactory;
    protected $fillable = ['product_id', 'from_unit', 'to_unit', 'conversion_factor'];
    protected $casts = ['conversion_factor' => 'decimal:4'];
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
''',

    'Inventory.php': '''<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

class Inventory extends Model
{
    use HasFactory;
    protected $table = 'inventory';

    protected $fillable = [
        'warehouse_id', 'product_id', 'rack_slot_id',
        'batch_number', 'expiry_date', 'quantity',
        'reserved_quantity', 'available_quantity', 'unit_cost',
    ];

    protected $casts = [
        'expiry_date' => 'date', 'quantity' => 'decimal:3',
        'reserved_quantity' => 'decimal:3', 'available_quantity' => 'decimal:3', 'unit_cost' => 'decimal:2',
    ];

    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function rackSlot(): BelongsTo { return $this->belongsTo(RackSlot::class); }
}
''',

    'InventoryTransaction.php': '''<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

class InventoryTransaction extends Model
{
    use HasFactory;
    public $timestamps = true;

    protected $fillable = [
        'transaction_number', 'type', 'warehouse_id', 'product_id', 'rack_slot_id',
        'batch_number', 'quantity', 'before_quantity', 'after_quantity',
        'direction', 'user_id', 'reference_type', 'reference_id', 'notes', 'metadata',
    ];

    protected $casts = [
        'quantity' => 'decimal:3', 'before_quantity' => 'decimal:3', 'after_quantity' => 'decimal:3',
        'metadata' => 'array',
    ];

    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function rackSlot(): BelongsTo { return $this->belongsTo(RackSlot::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
''',

    'Inbound.php': '''<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
use Illuminate\\Database\\Eloquent\\Relations\\HasMany;

class Inbound extends Model
{
    use HasFactory;

    protected $fillable = [
        'inbound_number', 'warehouse_id', 'user_id', 'status',
        'source_type', 'source_reference', 'expected_date', 'received_date', 'notes', 'metadata',
    ];

    protected $casts = ['expected_date' => 'date', 'received_date' => 'date', 'metadata' => 'array'];

    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function items(): HasMany { return $this->hasMany(InboundItem::class); }
}
''',

    'InboundItem.php': '''<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

class InboundItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'inbound_id', 'product_id', 'ordered_quantity', 'received_quantity',
        'batch_number', 'expiry_date', 'unit_cost', 'target_slot_code',
    ];
    protected $casts = [
        'ordered_quantity' => 'decimal:3', 'received_quantity' => 'decimal:3',
        'expiry_date' => 'date', 'unit_cost' => 'decimal:2',
    ];
    public function inbound(): BelongsTo { return $this->belongsTo(Inbound::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
''',

    'Outbound.php': '''<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
use Illuminate\\Database\\Eloquent\\Relations\\HasMany;

class Outbound extends Model
{
    use HasFactory;

    protected $fillable = [
        'outbound_number', 'warehouse_id', 'user_id', 'status',
        'destination_type', 'destination_reference', 'customer_name',
        'shipping_address', 'expected_date', 'shipped_date', 'notes',
    ];

    protected $casts = ['expected_date' => 'date', 'shipped_date' => 'date'];

    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function items(): HasMany { return $this->hasMany(OutboundItem::class); }
}
''',

    'OutboundItem.php': '''<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

class OutboundItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'outbound_id', 'product_id', 'ordered_quantity', 'picked_quantity', 'rack_slot_id', 'batch_number',
    ];
    protected $casts = [
        'ordered_quantity' => 'decimal:3', 'picked_quantity' => 'decimal:3',
    ];
    public function outbound(): BelongsTo { return $this->belongsTo(Outbound::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function rackSlot(): BelongsTo { return $this->belongsTo(RackSlot::class); }
}
''',

    'Transfer.php': '''<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
use Illuminate\\Database\\Eloquent\\Relations\\HasMany;

class Transfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_number', 'source_warehouse_id', 'dest_warehouse_id', 'user_id',
        'status', 'reason', 'approved_at', 'approved_by', 'received_at', 'received_by', 'notes',
    ];

    protected $casts = ['approved_at' => 'datetime', 'received_at' => 'datetime'];

    public function sourceWarehouse(): BelongsTo { return $this->belongsTo(Warehouse::class, 'source_warehouse_id'); }
    public function destWarehouse(): BelongsTo { return $this->belongsTo(Warehouse::class, 'dest_warehouse_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function approvedByUser(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function receivedByUser(): BelongsTo { return $this->belongsTo(User::class, 'received_by'); }
    public function items(): HasMany { return $this->hasMany(TransferItem::class); }
}
''',

    'TransferItem.php': '''<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

class TransferItem extends Model
{
    use HasFactory;
    protected $fillable = ['transfer_id', 'product_id', 'quantity', 'batch_number', 'source_slot_id', 'dest_slot_id'];
    protected $casts = ['quantity' => 'decimal:3'];
    public function transfer(): BelongsTo { return $this->belongsTo(Transfer::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function sourceSlot(): BelongsTo { return $this->belongsTo(RackSlot::class, 'source_slot_id'); }
    public function destSlot(): BelongsTo { return $this->belongsTo(RackSlot::class, 'dest_slot_id'); }
}
''',

    'StockOpname.php': '''<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
use Illuminate\\Database\\Eloquent\\Relations\\HasMany;

class StockOpname extends Model
{
    use HasFactory;

    protected $fillable = [
        'opname_number', 'warehouse_id', 'user_id', 'status', 'opname_type',
        'opname_date', 'submitted_at', 'approved_at', 'approved_by', 'notes',
    ];

    protected $casts = ['opname_date' => 'date', 'submitted_at' => 'datetime', 'approved_at' => 'datetime'];

    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function approvedByUser(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function items(): HasMany { return $this->hasMany(StockOpnameItem::class); }
}
''',

    'StockOpnameItem.php': '''<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

class StockOpnameItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'stock_opname_id', 'product_id', 'rack_slot_id', 'batch_number',
        'system_quantity', 'counted_quantity', 'variance', 'notes',
    ];
    protected $casts = [
        'system_quantity' => 'decimal:3', 'counted_quantity' => 'decimal:3', 'variance' => 'decimal:3',
    ];
    public function stockOpname(): BelongsTo { return $this->belongsTo(StockOpname::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function rackSlot(): BelongsTo { return $this->belongsTo(RackSlot::class); }
}
''',

    'Planogram.php': '''<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
use Illuminate\\Database\\Eloquent\\Relations\\HasMany;

class Planogram extends Model
{
    use HasFactory;

    protected $fillable = ['warehouse_id', 'version', 'canvas_data', 'canvas_settings', 'created_by', 'change_summary'];
    protected $casts = ['canvas_data' => 'array', 'canvas_settings' => 'array'];

    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function snapshots(): HasMany { return $this->hasMany(PlanogramSnapshot::class); }
}
''',

    'PlanogramSnapshot.php': '''<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

class PlanogramSnapshot extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $fillable = ['planogram_id', 'version', 'canvas_data', 'created_by', 'change_summary', 'created_at'];
    protected $casts = ['canvas_data' => 'array', 'created_at' => 'datetime'];

    public function planogram(): BelongsTo { return $this->belongsTo(Planogram::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
''',

    'AuditLog.php': '''<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'event', 'entity_type', 'entity_id',
        'old_values', 'new_values', 'ip_address', 'user_agent', 'created_at',
    ];
    protected $casts = [
        'old_values' => 'array', 'new_values' => 'array', 'created_at' => 'datetime',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
''',

    'Document.php': '''<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

class Document extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'name', 'original_name', 'type', 'size', 'path', 'disk'];
    protected $casts = ['size' => 'integer'];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
''',
}

models_dir = f'{base}/app/Models'
os.makedirs(models_dir, exist_ok=True)
for filename, content in models.items():
    path = f'{models_dir}/{filename}'
    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)
    print(f'Model: {filename}')

print(f'\nAll {len(models)} models created!')