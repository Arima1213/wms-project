import os

base = r'C:\Users\ASUS\Downloads\docker-setup\wms-project\backend'

def make_controller(name, model, has_warehouse=False, has_zone=False):
    if has_warehouse:
        param = 'int $warehouse'
        id_param = 'int $warehouse, int $id'
        store_param = f'int $warehouse, App\Http\Requests\\{name}StoreRequest $request'
        update_param = f'int $warehouse, int $id, App\Http\Requests\\{name}UpdateRequest $request'
    elif has_zone:
        param = 'int $zone'
        id_param = 'int $zone, int $id'
        store_param = f'int $zone, App\Http\Requests\\{name}StoreRequest $request'
        update_param = f'int $zone, int $id, App\Http\Requests\\{name}UpdateRequest $request'
    else:
        param = 'int $id'
        id_param = 'int $id'
        store_param = f'App\Http\Requests\\{name}StoreRequest $request'
        update_param = f'int $id, App\Http\Requests\\{name}UpdateRequest $request'

    store_import = f'App\\Http\\Requests\\{name}StoreRequest'
    update_import = f'App\\Http\\Requests\\{name}UpdateRequest'

    return f'''<?php

namespace App\\Http\\Controllers\\Api\\V1;

use App\\Http\\Controllers\\Controller;
use {store_import};
use {update_import};
use App\\Models\\{model};
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;

class {name}Controller extends Controller
{{
    public function index(Request $request{", " + param if param else ""}): JsonResponse
    {{
        $query = {model}::query()
{"            ->where('{model.lower()}able_id', $id)" if has_warehouse or has_zone else ""}
            ->when($request->has('search'), fn($q) => $q->where('name', 'ilike', '%' . $request->search . '%'))
            ->when($request->has('is_active'), fn($q) => $q->where('is_active', $request->is_active))
            ->with([{'"' + model.lower() + '"' if has_warehouse else "'zones'" if model == 'Warehouse' else "'zone'"}])
            ->orderBy('id');

        $data = $query->paginate($request->get('per_page', 15));
        return response()->json($data);
    }}

    public function store({store_param}): JsonResponse
    {{
        $data = $request->validated();
{"        $data['warehouse_id'] = $warehouse;" if has_warehouse else ""}
{"        $data['zone_id'] = $zone;" if has_zone else ""}
        $item = {model}::create($data);
        return response()->json(['data' => $item], 201);
    }}

    public function show({id_param}): JsonResponse
    {{
        $item = {model}::with([{'"' + model.lower() + '"' if has_warehouse else "'zone'" if has_zone else "'category'"}])->findOrFail($id);
        return response()->json(['data' => $item]);
    }}

    public function update({update_param}): JsonResponse
    {{
        $item = {model}::findOrFail($id);
        $item->update($request->validated());
        return response()->json(['data' => $item->fresh()]);
    }}

    public function destroy({id_param}): JsonResponse
    {{
        {model}::findOrFail($id)->delete();
        return response()->json(null, 204);
    }}
}}
'''

def make_request(name, is_store=True):
    return f'''<?php

namespace App\\Http\\Requests;

use Illuminate\\Foundation\\Http\\FormRequest;

class {name}StoreRequest extends FormRequest
{{
    public function authorize(): bool {{ return true; }}

    public function rules(): array
    {{
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20{" | unique:" + name.replace("Store","").lower() + "s,code" if is_store else ""}',
        ];
    }}
}}
'''

files = {}

# Base Controller
files['app/Http/Controllers/Controller.php'] = '''<?php

namespace App\\Http\\Controllers;

abstract class Controller
{
    //
}
'''

# Warehouse CRUD
files['app/Http/Controllers/Api/V1/WarehouseController.php'] = '''<?php

namespace App\\Http\\Controllers\\Api\\V1;

use App\\Http\\Controllers\\Controller;
use App\\Http\\Requests\\WarehouseStoreRequest;
use App\\Http\\Requests\\WarehouseUpdateRequest;
use App\\Models\\Warehouse;
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;

class WarehouseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Warehouse::query()
            ->when($request->has('search'), fn($q) => $q->where('name', 'ilike', '%' . $request->search . '%'))
            ->when($request->has('type'), fn($q) => $q->where('type', $request->type))
            ->when($request->has('is_active'), fn($q) => $q->where('is_active', $request->is_active))
            ->with(['zones:id,warehouse_id,code,name,is_active'])
            ->orderBy('name');

        $data = $query->paginate($request->get('per_page', 15));
        return response()->json($data);
    }

    public function store(WarehouseStoreRequest $request): JsonResponse
    {
        $warehouse = Warehouse::create($request->validated());
        return response()->json(['data' => $warehouse], 201);
    }

    public function show(int $id): JsonResponse
    {
        $warehouse = Warehouse::with([
            'zones.racks.levels.slots:id,rack_level_id,slot_code,column_number,status',
            'inventory' => fn($q) => $q->selectRaw('warehouse_id, COUNT(*) as count, SUM(quantity) as total_qty')->groupBy('warehouse_id'),
        ])->findOrFail($id);
        return response()->json(['data' => $warehouse]);
    }

    public function update(WarehouseUpdateRequest $request, int $id): JsonResponse
    {
        $warehouse = Warehouse::findOrFail($id);
        $warehouse->update($request->validated());
        return response()->json(['data' => $warehouse->fresh()]);
    }

    public function destroy(int $id): JsonResponse
    {
        Warehouse::findOrFail($id)->delete();
        return response()->json(null, 204);
    }

    public function summary(int $id): JsonResponse
    {
        $warehouse = Warehouse::withCount(['zones', 'inventory'])->findOrFail($id);
        return response()->json(['data' => [
            'total_zones' => $warehouse->zones_count,
            'total_inventory' => $warehouse->inventory_count,
            'total_value' => $warehouse->inventory()->sum(DB::raw('quantity * unit_cost')),
        ]]);
    }

    public function utilization(int $id): JsonResponse
    {
        $warehouse = Warehouse::with(['zones.racks.levels.slots'])->findOrFail($id);
        $allSlots = $warehouse->zones->flatMap(fn($z) => $z->racks->flatMap(fn($r) => $r->levels->flatMap(fn($l) => $l->slots)));
        $used = $allSlots->whereIn('status', ['partial', 'full'])->count();
        $total = $allSlots->count();
        return response()->json(['data' => [
            'total_slots' => $total,
            'used_slots' => $used,
            'empty_slots' => $total - $used,
            'utilization_percent' => $total > 0 ? round($used / $total * 100, 2) : 0,
        ]]);
    }
}
'''

# Zone CRUD
files['app/Http/Controllers/Api/V1/ZoneController.php'] = '''<?php

namespace App\\Http\\Controllers\\Api\\V1;

use App\\Http\\Controllers\\Controller;
use App\\Http\\Requests\\ZoneStoreRequest;
use App\\Http\\Requests\\ZoneUpdateRequest;
use App\\Models\\WarehouseZone;
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;

class ZoneController extends Controller
{
    public function index(Request $request, int $warehouse): JsonResponse
    {
        $query = WarehouseZone::where('warehouse_id', $warehouse)
            ->when($request->has('search'), fn($q) => $q->where('name', 'ilike', '%' . $request->search . '%'))
            ->with(['racks:id,zone_id,code,name,pos_x,pos_y'])
            ->orderBy('code');

        $data = $query->paginate($request->get('per_page', 20));
        return response()->json($data);
    }

    public function store(ZoneStoreRequest $request, int $warehouse): JsonResponse
    {
        $zone = WarehouseZone::create(array_merge($request->validated(), ['warehouse_id' => $warehouse]));
        return response()->json(['data' => $zone], 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => WarehouseZone::with('racks.levels.slots')->findOrFail($id)]);
    }

    public function update(ZoneUpdateRequest $request, int $id): JsonResponse
    {
        $zone = WarehouseZone::findOrFail($id);
        $zone->update($request->validated());
        return response()->json(['data' => $zone->fresh()]);
    }

    public function destroy(int $id): JsonResponse
    {
        WarehouseZone::findOrFail($id)->delete();
        return response()->json(null, 204);
    }

    public function activate(int $zone): JsonResponse
    {
        WarehouseZone::findOrFail($zone)->update(['is_active' => true]);
        return response()->json(['data' => ['is_active' => true]]);
    }

    public function deactivate(int $zone): JsonResponse
    {
        WarehouseZone::findOrFail($zone)->update(['is_active' => false]);
        return response()->json(['data' => ['is_active' => false]]);
    }
}
'''

# Rack CRUD
files['app/Http/Controllers/Api/V1/RackController.php'] = '''<?php

namespace App\\Http\\Controllers\\Api\\V1;

use App\\Http\\Controllers\\Controller;
use App\\Http\\Requests\\RackStoreRequest;
use App\\Http\\Requests\\RackUpdateRequest;
use App\\Models\\Rack;
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;

class RackController extends Controller
{
    public function index(Request $request, int $zone): JsonResponse
    {
        $query = Rack::where('zone_id', $zone)
            ->with(['levels.slots:id,rack_level_id,slot_code,column_number,status'])
            ->orderBy('code');

        $data = $query->paginate($request->get('per_page', 20));
        return response()->json($data);
    }

    public function store(RackStoreRequest $request, int $zone): JsonResponse
    {
        $data = array_merge($request->validated(), ['zone_id' => $zone]);
        $rack = Rack::create($data);

        // Auto-create levels and slots
        $levels = $data['levels'] ?? 3;
        $columns = $data['columns_per_level'] ?? 4;
        $zoneCode = $rack->zone->code;
        $rackCode = $rack->code;

        for ($l = 1; $l <= $levels; $l++) {
            $level = $rack->levels()->create([
                'level_number' => $l,
                'height_cm' => $data['level_height_cm'] ?? 30,
                'max_weight_kg' => $data['max_weight_kg'] ?? 100,
            ]);

            for ($s = 1; $s <= $columns; $s++) {
                $level->slots()->create([
                    'slot_code' => sprintf('%s-%s-L%d-S%d', $zoneCode, $rackCode, $l, $s),
                    'column_number' => $s,
                ]);
            }
        }

        return response()->json(['data' => $rack->load('levels.slots')], 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => Rack::with('levels.slots')->findOrFail($id)]);
    }

    public function update(RackUpdateRequest $request, int $id): JsonResponse
    {
        $rack = Rack::findOrFail($id);
        $rack->update($request->validated());
        return response()->json(['data' => $rack->fresh()]);
    }

    public function destroy(int $id): JsonResponse
    {
        Rack::findOrFail($id)->delete();
        return response()->json(null, 204);
    }

    public function slots(int $rack): JsonResponse
    {
        $rack = Rack::with('levels.slots')->findOrFail($rack);
        return response()->json(['data' => $rack->levels->flatMap(fn($l) => $l->slots)]);
    }

    public function updatePosition(Request $request, int $rack): JsonResponse
    {
        $rack = Rack::findOrFail($rack);
        $rack->update($request->validate(['pos_x' => 'required|integer', 'pos_y' => 'required|integer']));
        return response()->json(['data' => $rack]);
    }
}
'''

# RackSlot CRUD
files['app/Http/Controllers/Api/V1/RackSlotController.php'] = '''<?php

namespace App\\Http\\Controllers\\Api\\V1;

use App\\Http\\Controllers\\Controller;
use App\\Models\\RackSlot;
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;

class RackSlotController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = RackSlot::with(['rackLevel.rack.zone.warehouse:id,code,name', 'inventory.product:id,sku,name'])
            ->when($request->rack_id, fn($q) => $q->whereHas('rackLevel', fn($rq) => $rq->where('rack_id', $request->rack_id)))
            ->when($request->status, fn($q) => $q->where('status', $request->status));

        $data = $query->paginate($request->get('per_page', 30));
        return response()->json($data);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => RackSlot::with(['rackLevel.rack.zone.warehouse', 'inventory.product'])->findOrFail($id)]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $slot = RackSlot::findOrFail($id);
        $slot->update($request->validate(['slot_type' => 'sometimes|in:fixed,floating,reserved', 'status' => 'sometimes|in:empty,partial,full,reserved']));
        return response()->json(['data' => $slot->fresh()]);
    }

    public function assignProduct(Request $request, int $slot): JsonResponse
    {
        $slot = RackSlot::findOrFail($slot);
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'batch_number' => 'nullable|string|max:50',
            'expiry_date' => 'nullable|date',
        ]);
        $slot->update(['slot_type' => 'fixed', 'status' => 'partial']);
        return response()->json(['data' => $slot->fresh()->load('inventory.product')]);
    }

    public function unassignProduct(int $slot): JsonResponse
    {
        $slot = RackSlot::findOrFail($slot);
        $slot->inventory()->delete();
        $slot->update(['slot_type' => 'floating', 'status' => 'empty']);
        return response()->json(['data' => $slot->fresh()]);
    }

    public function reserve(Request $request, int $slot): JsonResponse
    {
        $slot = RackSlot::findOrFail($slot);
        $slot->update(['slot_type' => 'reserved', 'status' => 'reserved']);
        return response()->json(['data' => $slot]);
    }
}
'''

# Product CRUD
files['app/Http/Controllers/Api/V1/ProductController.php'] = '''<?php

namespace App\\Http\\Controllers\\Api\\V1;

use App\\Http\\Controllers\\Controller;
use App\\Http\\Requests\\ProductStoreRequest;
use App\\Http\\Requests\\ProductUpdateRequest;
use App\\Models\\Product;
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::with('category:id,name')
            ->when($request->has('search'), fn($q) => $q->where('name', 'ilike', '%' . $request->search . '%')->orWhere('sku', 'ilike', '%' . $request->search . '%'))
            ->when($request->category_id, fn($q) => $q->where('category_id', $request->category_id))
            ->when($request->has('is_active'), fn($q) => $q->where('is_active', $request->is_active))
            ->orderBy('name');

        $data = $query->paginate($request->get('per_page', 20));
        return response()->json($data);
    }

    public function store(ProductStoreRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());
        return response()->json(['data' => $product], 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => Product::with('category', 'barcodes', 'uomConversions')->findOrFail($id)]);
    }

    public function update(ProductUpdateRequest $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $product->update($request->validated());
        return response()->json(['data' => $product->fresh()]);
    }

    public function destroy(int $id): JsonResponse
    {
        Product::findOrFail($id)->delete();
        return response()->json(null, 204);
    }

    public function search(Request $request): JsonResponse
    {
        $q = $request->get('q', '');
        $products = Product::where('name', 'ilike', '%' . $q . '%')
            ->orWhere('sku', 'ilike', '%' . $q . '%')
            ->orWhere('barcode', 'ilike', '%' . $q . '%')
            ->limit(20)
            ->get(['id', 'sku', 'name', 'barcode', 'selling_price', 'unit']);

        return response()->json(['data' => $products]);
    }

    public function import(Request $request): JsonResponse
    {
        // TODO: implement via queue job with PhpSpreadsheet
        return response()->json(['message' => 'Import queued'], 202);
    }

    public function locations(int $product): JsonResponse
    {
        $product = Product::findOrFail($product);
        $locations = $product->inventory()
            ->with(['warehouse:id,code,name', 'rackSlot:id,slot_code', 'rackSlot.rackLevel.rack:id,code'])
            ->where('quantity', '>', 0)->get();

        return response()->json(['data' => $locations]);
    }
}
'''

# Category CRUD
files['app/Http/Controllers/Api/V1/CategoryController.php'] = '''<?php

namespace App\\Http\\Controllers\\Api\\V1;

use App\\Http\\Controllers\\Controller;
use App\\Http\\Requests\\CategoryStoreRequest;
use App\\Http\\Requests\\CategoryUpdateRequest;
use App\\Models\\ProductCategory;
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ProductCategory::with('parent:id,name')
            ->when($request->has('search'), fn($q) => $q->where('name', 'ilike', '%' . $request->search . '%'))
            ->orderBy('name');

        $data = $query->paginate($request->get('per_page', 50));
        return response()->json($data);
    }

    public function store(CategoryStoreRequest $request): JsonResponse
    {
        $category = ProductCategory::create($request->validated());
        return response()->json(['data' => $category], 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => ProductCategory::with(['parent', 'children', 'products'])->findOrFail($id)]);
    }

    public function update(CategoryUpdateRequest $request, int $id): JsonResponse
    {
        $category = ProductCategory::findOrFail($id);
        $category->update($request->validated());
        return response()->json(['data' => $category->fresh()]);
    }

    public function destroy(int $id): JsonResponse
    {
        ProductCategory::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
'''

# Inbound CRUD
files['app/Http/Controllers/Api/V1/InboundController.php'] = '''<?php

namespace App\\Http\\Controllers\\Api\\V1;

use App\\Http\\Controllers\\Controller;
use App\\Models\\{Inbound, Inventory, InventoryTransaction, RackSlot};
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;

class InboundController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Inbound::with('warehouse:id,code,name', 'user:id,name')
            ->when($request->warehouse_id, fn($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at');

        $data = $query->paginate($request->get('per_page', 20));
        return response()->json($data);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'source_type' => 'required|in:purchase_order,return_supplier,return_customer,transfer_in,other',
            'source_reference' => 'nullable|string|max:100',
            'expected_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.ordered_quantity' => 'required|numeric|min:0.001',
            'items.*.batch_number' => 'nullable|string|max:50',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.unit_cost' => 'nullable|numeric|min:0',
            'items.*.target_slot_code' => 'nullable|string|max:20',
        ]);

        $inbound = Inbound::create([
            'inbound_number' => 'IN-' . date('Ymd') . '-' . str_pad(Inbound::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT),
            'warehouse_id' => $validated['warehouse_id'],
            'user_id' => $request->user()->id,
            'source_type' => $validated['source_type'],
            'source_reference' => $validated['source_reference'] ?? null,
            'expected_date' => $validated['expected_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 'draft',
        ]);

        foreach ($validated['items'] as $item) {
            $inbound->items()->create($item);
        }

        return response()->json(['data' => $inbound->load('items.product')], 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => Inbound::with(['warehouse', 'user', 'items.product'])->findOrFail($id)]);
    }

    public function receive(Request $request, int $id): JsonResponse
    {
        $inbound = Inbound::with('items')->findOrFail($id);
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.inbound_item_id' => 'required|exists:inbound_items,id',
            'items.*.received_quantity' => 'required|numeric|min:0',
            'items.*.batch_number' => 'nullable|string|max:50',
            'items.*.expiry_date' => 'nullable|date',
            'target_slot_code' => 'nullable|string|max:20',
        ]);

        $user = $request->user();
        $txNum = 'GR-' . date('YmdHis') . '-' . str_pad($inbound->id, 4, '0', STR_PAD_LEFT);

        foreach ($validated['items'] as $itemData) {
            $item = $inbound->items()->findOrFail($itemData['inbound_item_id']);
            $item->update([
                'received_quantity' => $itemData['received_quantity'],
                'batch_number' => $itemData['batch_number'] ?? $item->batch_number,
                'expiry_date' => $itemData['expiry_date'] ?? $item->expiry_date,
            ]);

            if ($itemData['received_quantity'] > 0) {
                // Find or create inventory
                $slot = null;
                if (!empty($validated['target_slot_code'])) {
                    $slot = RackSlot::where('slot_code', $validated['target_slot_code'])->first();
                }

                $inv = Inventory::updateOrCreate(
                    [
                        'warehouse_id' => $inbound->warehouse_id,
                        'product_id' => $item->product_id,
                        'rack_slot_id' => $slot?->id,
                        'batch_number' => $itemData['batch_number'] ?? $item->batch_number,
                    ],
                    [
                        'expiry_date' => $itemData['expiry_date'] ?? $item->expiry_date,
                        'unit_cost' => $item->unit_cost,
                    ]
                );

                $beforeQty = $inv->quantity;
                $inv->quantity += $itemData['received_quantity'];
                $inv->available_quantity = $inv->quantity - $inv->reserved_quantity;
                $inv->save();

                InventoryTransaction::create([
                    'transaction_number' => $txNum,
                    'type' => 'GR',
                    'warehouse_id' => $inbound->warehouse_id,
                    'product_id' => $item->product_id,
                    'rack_slot_id' => $slot?->id,
                    'batch_number' => $itemData['batch_number'] ?? $item->batch_number,
                    'quantity' => $itemData['received_quantity'],
                    'before_quantity' => $beforeQty,
                    'after_quantity' => $inv->quantity,
                    'direction' => 'in',
                    'user_id' => $user->id,
                    'reference_type' => 'Inbound',
                    'reference_id' => $inbound->id,
                ]);
            }
        }

        // Update status
        $allReceived = $inbound->items()->whereColumn('received_quantity', '<', 'ordered_quantity')->count() === 0;
        $anyReceived = $inbound->items()->where('received_quantity', '>', 0)->count() > 0;
        $inbound->update([
            'status' => $allReceived ? 'received' : ($anyReceived ? 'partial_received' : $inbound->status),
            'received_date' => now(),
        ]);

        return response()->json(['data' => $inbound->fresh()->load('items.product')]);
    }

    public function cancel(int $id): JsonResponse
    {
        $inbound = Inbound::findOrFail($id);
        if (in_array($inbound->status, ['received', 'cancelled'])) {
            return response()->json(['message' => 'Cannot cancel this inbound'], 422);
        }
        $inbound->update(['status' => 'cancelled']);
        return response()->json(['data' => $inbound]);
    }
}
'''

# Outbound CRUD
files['app/Http/Controllers/Api/V1/OutboundController.php'] = '''<?php

namespace App\\Http\\Controllers\\Api\\V1;

use App\\Http\\Controllers\\Controller;
use App\\Models\\{Outbound, Inventory, InventoryTransaction};
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;

class OutboundController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Outbound::with('warehouse:id,code,name', 'user:id,name')
            ->when($request->warehouse_id, fn($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at');

        $data = $query->paginate($request->get('per_page', 20));
        return response()->json($data);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'destination_type' => 'required|in:sales_order,production,transfer_out,sample,other',
            'destination_reference' => 'nullable|string|max:100',
            'customer_name' => 'nullable|string|max:200',
            'shipping_address' => 'nullable|string',
            'expected_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.ordered_quantity' => 'required|numeric|min:0.001',
        ]);

        $outbound = Outbound::create([
            'outbound_number' => 'OUT-' . date('Ymd') . '-' . str_pad(Outbound::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT),
            'warehouse_id' => $validated['warehouse_id'],
            'user_id' => $request->user()->id,
            'destination_type' => $validated['destination_type'],
            'destination_reference' => $validated['destination_reference'] ?? null,
            'customer_name' => $validated['customer_name'] ?? null,
            'shipping_address' => $validated['shipping_address'] ?? null,
            'expected_date' => $validated['expected_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 'draft',
        ]);

        foreach ($validated['items'] as $item) {
            $outbound->items()->create($item);
        }

        return response()->json(['data' => $outbound->load('items.product')], 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => Outbound::with(['warehouse', 'user', 'items.product'])->findOrFail($id)]);
    }

    public function pick(Request $request, int $id): JsonResponse
    {
        $outbound = Outbound::with('items')->findOrFail($id);
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.outbound_item_id' => 'required|exists:outbound_items,id',
            'items.*.picked_quantity' => 'required|numeric|min:0',
        ]);

        $txNum = 'GI-' . date('YmdHis') . '-' . str_pad($outbound->id, 4, '0', STR_PAD_LEFT);
        $user = $request->user();

        foreach ($validated['items'] as $itemData) {
            $item = $outbound->items()->findOrFail($itemData['outbound_item_id']);
            $item->update(['picked_quantity' => $itemData['picked_quantity']]);

            if ($itemData['picked_quantity'] > 0) {
                $inv = Inventory::where('warehouse_id', $outbound->warehouse_id)
                    ->where('product_id', $item->product_id)
                    ->where('quantity', '>', 0)->first();

                $beforeQty = $inv?->quantity ?? 0;
                if ($inv) {
                    $inv->quantity -= $itemData['picked_quantity'];
                    $inv->available_quantity = $inv->quantity - $inv->reserved_quantity;
                    $inv->save();
                }

                InventoryTransaction::create([
                    'transaction_number' => $txNum,
                    'type' => 'GI',
                    'warehouse_id' => $outbound->warehouse_id,
                    'product_id' => $item->product_id,
                    'rack_slot_id' => $inv?->rack_slot_id,
                    'batch_number' => $inv?->batch_number,
                    'quantity' => $itemData['picked_quantity'],
                    'before_quantity' => $beforeQty,
                    'after_quantity' => $inv ? $inv->quantity : ($beforeQty - $itemData['picked_quantity']),
                    'direction' => 'out',
                    'user_id' => $user->id,
                    'reference_type' => 'Outbound',
                    'reference_id' => $outbound->id,
                ]);
            }
        }

        $outbound->update(['status' => 'picked']);
        return response()->json(['data' => $outbound->fresh()->load('items.product')]);
    }

    public function ship(int $id): JsonResponse
    {
        $outbound = Outbound::findOrFail($id);
        $outbound->update(['status' => 'shipped', 'shipped_date' => now()]);
        return response()->json(['data' => $outbound]);
    }

    public function cancel(int $id): JsonResponse
    {
        $outbound = Outbound::findOrFail($id);
        if (in_array($outbound->status, ['delivered', 'cancelled'])) {
            return response()->json(['message' => 'Cannot cancel this outbound'], 422);
        }
        $outbound->update(['status' => 'cancelled']);
        return response()->json(['data' => $outbound]);
    }
}
'''

# Transfer CRUD
files['app/Http/Controllers/Api/V1/TransferController.php'] = '''<?php

namespace App\\Http\\Controllers\\Api\\V1;

use App\\Http\\Controllers\\Controller;
use App\\Models\\{Transfer, Inventory, InventoryTransaction};
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;

class TransferController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Transfer::with('sourceWarehouse:id,code,name', 'destWarehouse:id,code,name', 'user:id,name')
            ->when($request->source_warehouse_id, fn($q) => $q->where('source_warehouse_id', $request->source_warehouse_id))
            ->when($request->dest_warehouse_id, fn($q) => $q->where('dest_warehouse_id', $request->dest_warehouse_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at');

        $data = $query->paginate($request->get('per_page', 20));
        return response()->json($data);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source_warehouse_id' => 'required|different:dest_warehouse_id|exists:warehouses,id',
            'dest_warehouse_id' => 'required|exists:warehouses,id',
            'reason' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
        ]);

        $transfer = Transfer::create([
            'transfer_number' => 'TR-' . date('Ymd') . '-' . str_pad(Transfer::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT),
            'source_warehouse_id' => $validated['source_warehouse_id'],
            'dest_warehouse_id' => $validated['dest_warehouse_id'],
            'user_id' => $request->user()->id,
            'reason' => $validated['reason'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 'pending_approval',
        ]);

        foreach ($validated['items'] as $item) {
            $transfer->items()->create($item);
        }

        return response()->json(['data' => $transfer->load('items.product')], 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => Transfer::with(['sourceWarehouse', 'destWarehouse', 'user', 'items.product'])->findOrFail($id)]);
    }

    public function approve(int $id): JsonResponse
    {
        $transfer = Transfer::findOrFail($id);
        $transfer->update(['status' => 'approved', 'approved_at' => now(), 'approved_by' => auth()->id()]);
        return response()->json(['data' => $transfer]);
    }

    public function reject(int $id): JsonResponse
    {
        $transfer = Transfer::findOrFail($id);
        $transfer->update(['status' => 'rejected']);
        return response()->json(['data' => $transfer]);
    }

    public function execute(int $id): JsonResponse
    {
        $transfer = Transfer::with('items')->findOrFail($id);
        $txNum = 'TR-' . date('YmdHis') . '-' . str_pad($transfer->id, 4, '0', STR_PAD_LEFT);
        $user = $request = request();
        $user = $user->user();

        foreach ($transfer->items as $item) {
            $srcInv = Inventory::where('warehouse_id', $transfer->source_warehouse_id)
                ->where('product_id', $item->product_id)->where('quantity', '>', 0)->first();

            if ($srcInv) {
                $beforeQty = $srcInv->quantity;
                $srcInv->quantity -= $item->quantity;
                $srcInv->available_quantity = $srcInv->quantity - $srcInv->reserved_quantity;
                $srcInv->save();

                InventoryTransaction::create([
                    'transaction_number' => $txNum . '-OUT',
                    'type' => 'TR',
                    'warehouse_id' => $transfer->source_warehouse_id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'before_quantity' => $beforeQty,
                    'after_quantity' => $srcInv->quantity,
                    'direction' => 'out',
                    'user_id' => $user->id,
                    'reference_type' => 'Transfer',
                    'reference_id' => $transfer->id,
                ]);

                // Add to dest warehouse
                $destInv = Inventory::firstOrCreate(
                    ['warehouse_id' => $transfer->dest_warehouse_id, 'product_id' => $item->product_id, 'rack_slot_id' => null, 'batch_number' => $srcInv->batch_number],
                    ['quantity' => 0, 'reserved_quantity' => 0, 'available_quantity' => 0, 'unit_cost' => $srcInv->unit_cost]
                );
                $destBefore = $destInv->quantity;
                $destInv->quantity += $item->quantity;
                $destInv->available_quantity = $destInv->quantity - $destInv->reserved_quantity;
                $destInv->save();

                InventoryTransaction::create([
                    'transaction_number' => $txNum . '-IN',
                    'type' => 'TR',
                    'warehouse_id' => $transfer->dest_warehouse_id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'before_quantity' => $destBefore,
                    'after_quantity' => $destInv->quantity,
                    'direction' => 'in',
                    'user_id' => $user->id,
                    'reference_type' => 'Transfer',
                    'reference_id' => $transfer->id,
                ]);
            }
        }

        $transfer->update(['status' => 'received', 'received_at' => now(), 'received_by' => $user->id]);
        return response()->json(['data' => $transfer->fresh()]);
    }
}
'''

# StockOpname CRUD
files['app/Http/Controllers/Api/V1/StockOpnameController.php'] = '''<?php

namespace App\\Http\\Controllers\\Api\\V1;

use App\\Http\\Controllers\\Controller;
use App\\Models\\{StockOpname, Inventory, InventoryTransaction};
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;

class StockOpnameController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = StockOpname::with('warehouse:id,code,name', 'user:id,name')
            ->when($request->warehouse_id, fn($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at');

        $data = $query->paginate($request->get('per_page', 20));
        return response()->json($data);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'opname_type' => 'required|in:full,cycle_count',
            'notes' => 'nullable|string',
        ]);

        $opname = StockOpname::create([
            'opname_number' => 'SO-' . date('Ymd') . '-' . str_pad(StockOpname::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT),
            'warehouse_id' => $validated['warehouse_id'],
            'user_id' => $request->user()->id,
            'opname_type' => $validated['opname_type'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'draft',
        ]);

        return response()->json(['data' => $opname], 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => StockOpname::with(['warehouse', 'user', 'items.product'])->findOrFail($id)]);
    }

    public function start(int $id): JsonResponse
    {
        $opname = StockOpname::with('items')->findOrFail($id);
        if ($opname->status !== 'draft') {
            return response()->json(['message' => 'Only draft opname can be started'], 422);
        }

        // Generate items from current inventory if empty
        if ($opname->items()->count() === 0) {
            $inventory = Inventory::where('warehouse_id', $opname->warehouse_id)->get();
            foreach ($inventory as $inv) {
                $opname->items()->create([
                    'product_id' => $inv->product_id,
                    'rack_slot_id' => $inv->rack_slot_id,
                    'batch_number' => $inv->batch_number,
                    'system_quantity' => $inv->quantity,
                ]);
            }
        }

        $opname->update(['status' => 'in_progress', 'opname_date' => now()]);
        return response()->json(['data' => $opname->fresh()->load('items.product')]);
    }

    public function submit(Request $request, int $id): JsonResponse
    {
        $opname = StockOpname::with('items')->findOrFail($id);
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.stock_opname_item_id' => 'required|exists:stock_opname_items,id',
            'items.*.counted_quantity' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string',
        ]);

        foreach ($validated['items'] as $itemData) {
            $item = $opname->items()->findOrFail($itemData['stock_opname_item_id']);
            $item->update([
                'counted_quantity' => $itemData['counted_quantity'],
                'variance' => $itemData['counted_quantity'] - $item->system_quantity,
                'notes' => $itemData['notes'] ?? null,
            ]);
        }

        $opname->update(['status' => 'submitted', 'submitted_at' => now()]);
        return response()->json(['data' => $opname->fresh()->load('items.product')]);
    }

    public function approve(int $id): JsonResponse
    {
        $opname = StockOpname::with('items')->findOrFail($id);
        $txNum = 'ADJ-' . date('YmdHis') . '-' . str_pad($opname->id, 4, '0', STR_PAD_LEFT);
        $user = request()->user();

        foreach ($opname->items as $item) {
            if ($item->variance != 0) {
                $inv = Inventory::where('warehouse_id', $opname->warehouse_id)
                    ->where('product_id', $item->product_id)
                    ->where(fn($q) => $item->rack_slot_id ? $q->where('rack_slot_id', $item->rack_slot_id) : $q->whereNull('rack_slot_id'))
                    ->first();

                if ($inv) {
                    $beforeQty = $inv->quantity;
                    $inv->quantity = $item->counted_quantity;
                    $inv->available_quantity = $inv->quantity - $inv->reserved_quantity;
                    $inv->save();

                    InventoryTransaction::create([
                        'transaction_number' => $txNum,
                        'type' => $item->variance > 0 ? 'ADJ+' : 'ADJ-',
                        'warehouse_id' => $opname->warehouse_id,
                        'product_id' => $item->product_id,
                        'rack_slot_id' => $inv->rack_slot_id,
                        'quantity' => abs($item->variance),
                        'before_quantity' => $beforeQty,
                        'after_quantity' => $inv->quantity,
                        'direction' => $item->variance > 0 ? 'in' : 'out',
                        'user_id' => $user->id,
                        'reference_type' => 'StockOpname',
                        'reference_id' => $opname->id,
                        'notes' => 'Stock opname adjustment',
                    ]);
                }
            }
        }

        $opname->update(['status' => 'approved', 'approved_at' => now(), 'approved_by' => $user->id]);
        return response()->json(['data' => $opname->fresh()]);
    }
}
'''

# Form Requests
requests = {
    'app/Http/Requests/WarehouseStoreRequest.php': '''<?php
namespace App\\Http\\Requests;
use Illuminate\\Foundation\\Http\\FormRequest;
class WarehouseStoreRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'code' => 'required|string|max:20|unique:warehouses,code',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'capacity_m2' => 'nullable|numeric|min:0',
            'type' => 'required|in:regular,cold_storage,bonded,consignment',
            'pic_name' => 'nullable|string|max:255',
            'pic_phone' => 'nullable|string|max:20',
            'pic_email' => 'nullable|email',
            'operating_hours' => 'nullable|array',
            'is_active' => 'boolean',
        ];
    }
}
''',
    'app/Http/Requests/WarehouseUpdateRequest.php': '''<?php
namespace App\\Http\\Requests;
use Illuminate\\Foundation\\Http\\FormRequest;
class WarehouseUpdateRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return ['name' => 'sometimes|string|max:255', 'is_active' => 'sometimes|boolean', 'pic_name' => 'nullable|string', 'pic_phone' => 'nullable|string', 'pic_email' => 'nullable|email'];
    }
}
''',
    'app/Http/Requests/ZoneStoreRequest.php': '''<?php
namespace App\\Http\\Requests;
use Illuminate\\Foundation\\Http\\FormRequest;
class ZoneStoreRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return ['code' => 'required|string|max:10|unique:warehouse_zones,code', 'name' => 'required|string|max:255', 'zone_type' => 'required|in:fast_moving,slow_moving,heavy,cold,hazmat,general', 'min_temp' => 'nullable|numeric', 'max_temp' => 'nullable|numeric', 'is_active' => 'boolean'];
    }
}
''',
    'app/Http/Requests/ZoneUpdateRequest.php': '''<?php
namespace App\\Http\\Requests;
use Illuminate\\Foundation\\Http\\FormRequest;
class ZoneUpdateRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return ['name' => 'sometimes|string', 'zone_type' => 'sometimes', 'is_active' => 'boolean'];
    }
}
''',
    'app/Http/Requests/RackStoreRequest.php': '''<?php
namespace App\\Http\\Requests;
use Illuminate\\Foundation\\Http\\FormRequest;
class RackStoreRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'code' => 'required|string|max:20',
            'name' => 'nullable|string|max:255',
            'pos_x' => 'nullable|integer|min:0',
            'pos_y' => 'nullable|integer|min:0',
            'width_cm' => 'nullable|numeric|min:1',
            'depth_cm' => 'nullable|numeric|min:1',
            'height_cm' => 'nullable|numeric|min:1',
            'levels' => 'nullable|integer|min:1|max:10',
            'columns_per_level' => 'nullable|integer|min:1|max:20',
            'max_weight_kg' => 'nullable|numeric|min:1',
            'orientation' => 'nullable|in:horizontal,vertical',
        ];
    }
}
''',
    'app/Http/Requests/RackUpdateRequest.php': '''<?php
namespace App\\Http\\Requests;
use Illuminate\\Foundation\\Http\\FormRequest;
class RackUpdateRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return ['name' => 'sometimes|string', 'orientation' => 'sometimes|in:horizontal,vertical'];
    }
}
''',
    'app/Http/Requests/ProductStoreRequest.php': '''<?php
namespace App\\Http\\Requests;
use Illuminate\\Foundation\\Http\\FormRequest;
class ProductStoreRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'sku' => 'required|string|max:50|unique:products,sku',
            'barcode' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:product_categories,id',
            'unit' => 'required|string|max:20',
            'length_cm' => 'nullable|numeric|min:0',
            'width_cm' => 'nullable|numeric|min:0',
            'height_cm' => 'nullable|numeric|min:0',
            'weight_kg' => 'nullable|numeric|min:0',
            'min_stock' => 'nullable|numeric|min:0',
            'max_stock' => 'nullable|numeric|min:0',
            'safety_stock' => 'nullable|numeric|min:0',
            'reorder_point' => 'nullable|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ];
    }
}
''',
    'app/Http/Requests/ProductUpdateRequest.php': '''<?php
namespace App\\Http\\Requests;
use Illuminate\\Foundation\\Http\\FormRequest;
class ProductUpdateRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return ['name' => 'sometimes|string|max:255', 'selling_price' => 'sometimes|numeric|min:0', 'is_active' => 'sometimes|boolean'];
    }
}
''',
    'app/Http/Requests/CategoryStoreRequest.php': '''<?php
namespace App\\Http\\Requests;
use Illuminate\\Foundation\\Http\\FormRequest;
class CategoryStoreRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return ['parent_id' => 'nullable|exists:product_categories,id', 'code' => 'required|string|max:20|unique:product_categories,code', 'name' => 'required|string|max:255', 'is_active' => 'boolean'];
    }
}
''',
    'app/Http/Requests/CategoryUpdateRequest.php': '''<?php
namespace App\\Http\\Requests;
use Illuminate\\Foundation\\Http\\FormRequest;
class CategoryUpdateRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return ['name' => 'sometimes|string', 'is_active' => 'sometimes|boolean'];
    }
}
''',
}

all_files = {**files, **requests}
for fname, content in all_files.items():
    path = f'{base}/{fname}'
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)
    print(f'Created: {fname}')

print(f'\nAll {len(all_files)} files created!')