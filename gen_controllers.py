import os

base = r'C:\Users\ASUS\Downloads\docker-setup\wms-project\backend'

def ctl(name, model, api_resource=None):
    """Generate a standard API controller"""
    api_res = api_resource or f'{name}Resource'
    return f'''<?php

namespace App\\Http\\Controllers\\Api\\V1;

use App\\Http\\Controllers\\Controller;
use App\\Http\\Requests\\{name}StoreRequest;
use App\\Http\\Requests\\{name}UpdateRequest;
use App\\Http\\Resources\\{api_res};
use App\\Models\\{model};
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;

class {name}Controller extends Controller
{{
    public function index(Request $request): JsonResponse
    {{
        $query = {model}::query();
        if ($request->has('search')) {{
            $query->where('name', 'ilike', '%' . $request->search . '%');
        }}
        $data = $query->paginate($request->get('per_page', 15));
        return response()->json($data);
    }}

    public function store({name}StoreRequest $request): JsonResponse
    {{
        $item = {model}::create($request->validated());
        return response()->json(['data' => new {api_res}($item)], 201);
    }}

    public function show(int $id): JsonResponse
    {{
        $item = {model}::findOrFail($id);
        return response()->json(['data' => new {api_res}($item)]);
    }}

    public function update({name}UpdateRequest $request, int $id): JsonResponse
    {{
        $item = {model}::findOrFail($id);
        $item->update($request->validated());
        return response()->json(['data' => new {api_res}($item->fresh())]);
    }}

    public function destroy(int $id): JsonResponse
    {{
        {model}::findOrFail($id)->delete();
        return response()->json(null, 204);
    }}
}}
'''

def req(name, is_store=True):
    if is_store:
        return f'''<?php

namespace App\\Http\\Requests;

use Illuminate\\Foundation\\Http\\FormRequest;

class {name}StoreRequest extends FormRequest
{{
    public function authorize(): bool {{ return true; }}

    public function rules(): array
    {{
        return [
            // Define validation rules for {name}
        ];
    }}
}}
'''
    else:
        return f'''<?php

namespace App\\Http\\Requests;

use Illuminate\\Foundation\\Http\\FormRequest;

class {name}UpdateRequest extends FormRequest
{{
    public function authorize(): bool {{ return true; }}

    public function rules(): array
    {{
        return [
            // Define validation rules for {name}
        ];
    }}
}}
'''

def resource(name):
    return f'''<?php

namespace App\\Http\\Resources;

use Illuminate\\Http\\Request;
use Illuminate\\Http\\Resources\\Json\\JsonResource;

class {name}Resource extends JsonResource
{{
    public function toArray(Request $request): array
    {{
        return [
            'id' => $this->id,
        ];
    }}
}}
'''

def service(name, model):
    return f'''<?php

namespace App\\Services;

use App\\Models\\{model};
use Illuminate\\Database\\Eloquent\\Collection;
use Illuminate\\Support\\Facades\\DB;

class {name}Service
{{
    public function list(array $filters): Collection
    {{
        $query = {model}::query();
        return $query->get();
    }}

    public function create(array $data): {model}
    {{
        return {model}::create($data);
    }}

    public function update(int $id, array $data): {model}
    {{
        $item = {model}::findOrFail($id);
        $item->update($data);
        return $item->fresh();
    }}

    public function delete(int $id): void
    {{
        {model}::findOrFail($id)->delete();
    }}
}}
'''

# Controllers
controllers = {
    'AuthController.php': '''<?php

namespace App\\Http\\Controllers\\Api\\V1;

use App\\Http\\Controllers\\Controller;
use App\\Models\\User;
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\Auth;
use Illuminate\\Support\\Facades\\Hash;
use Illuminate\\Validation\\Rules\\Password;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = Auth::user();
        if (!$user->is_active) {
            Auth::logout();
            return response()->json(['message' => 'Account is deactivated'], 403);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'abilities' => $user->getPermissionNames(),
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json(['user' => $user, 'token' => $token], 201);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => $request->user()]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'phone' => 'sometimes|string|max:20',
        ]);
        $user->update($validated);
        return response()->json(['data' => $user->fresh()]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = $request->user();
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password is incorrect'], 422);
        }

        $user->update(['password' => Hash::make($request->password)]);
        return response()->json(['message' => 'Password updated']);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        // TODO: Implement with Laravel notifications
        return response()->json(['message' => 'Password reset link sent']);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        // TODO: Implement with Laravel notifications
        return response()->json(['message' => 'Password reset successfully']);
    }
}
''',

    'DashboardController.php': '''<?php

namespace App\\Http\\Controllers\\Api\\V1;

use App\\Http\\Controllers\\Controller;
use App\\Models\\{Inventory, InventoryTransaction, Product, Warehouse, Inbound, Outbound};
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $warehouseId = $request->get('warehouse_id');

        $baseQuery = fn($model) => $model::query()
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId));

        // Key metrics
        $totalSku = Product::query()->when($warehouseId, fn($q) => $q->whereHas('inventory', fn($iq) => $iq->where('warehouse_id', $warehouseId)))->count();
        $totalStockValue = Inventory::query()
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->selectRaw('SUM(quantity * unit_cost) as total')->value('total') ?? 0;

        $todayTransactions = (clone $baseQuery(new InventoryTransaction))
            ->whereDate('created_at', now())->count();

        $todayInbounds = (clone $baseQuery(new Inbound))
            ->whereDate('created_at', now())->count();

        $todayOutbounds = (clone $baseQuery(new Outbound))
            ->whereDate('created_at', now())->count();

        // Low stock alerts
        $lowStockAlerts = Inventory::query()
            ->with('product')
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->whereColumn('quantity', '<=', 'product.reorder_point')
            ->where('quantity', '>', 0)
            ->count();

        // Near expiry alerts
        $nearExpiryAlerts = Inventory::query()
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now(), now()->addDays(30)])
            ->count();

        // Warehouse utilization
        $warehouses = Warehouse::withCount(['zones', 'inventory'])->get()->map(fn($w) => [
            'id' => $w->id, 'name' => $w->name, 'code' => $w->code,
            'zone_count' => $w->zones_count, 'inventory_count' => $w->inventory_count,
        ]);

        // Recent transactions
        $recentTx = InventoryTransaction::with(['product:id,name,sku', 'user:id,name', 'warehouse:id,name'])
            ->orderByDesc('created_at')->limit(10)->get();

        return response()->json([
            'data' => [
                'total_sku' => $totalSku,
                'total_stock_value' => round($totalStockValue, 2),
                'today_transactions' => $todayTransactions,
                'today_inbounds' => $todayInbounds,
                'today_outbounds' => $todayOutbounds,
                'low_stock_alerts' => $lowStockAlerts,
                'near_expiry_alerts' => $nearExpiryAlerts,
                'warehouses' => $warehouses,
                'recent_transactions' => $recentTx,
            ]
        ]);
    }
}
''',

    'InventoryController.php': '''<?php

namespace App\\Http\\Controllers\\Api\\V1;

use App\\Http\\Controllers\\Controller;
use App\\Models\\{Inventory, InventoryTransaction, Product};
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;

class InventoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Inventory::with(['product:id,sku,name,barcode', 'warehouse:id,code,name', 'rackSlot:id,slot_code'])
            ->when($request->warehouse_id, fn($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->product_id, fn($q) => $q->where('product_id', $request->product_id))
            ->when($request->has('low_stock'), fn($q) => $q->whereColumn('quantity', '<=', 'product.reorder_point'));

        $data = $query->paginate($request->get('per_page', 20));
        return response()->json($data);
    }

    public function stock(Request $request): JsonResponse
    {
        $query = Product::withSum(['inventory as total_qty' => fn($q) => $request->warehouse_id ? $q->where('warehouse_id', $request->warehouse_id) : null], 'quantity')
            ->withSum(['inventory as reserved_qty' => fn($q) => $request->warehouse_id ? $q->where('warehouse_id', $request->warehouse_id) : null], 'reserved_quantity');

        $data = $query->paginate($request->get('per_page', 20));
        return response()->json($data);
    }

    public function alerts(Request $request): JsonResponse
    {
        $warehouseId = $request->warehouse_id;
        $lowStock = Inventory::with('product')
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->whereColumn('quantity', '<=', 'product.reorder_point')
            ->get();

        $nearExpiry = Inventory::with('product')
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now(), now()->addDays(30)])
            ->get();

        $outOfStock = Inventory::with('product')
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->where('quantity', '<=', 0)
            ->get();

        return response()->json([
            'low_stock' => $lowStock,
            'near_expiry' => $nearExpiry,
            'out_of_stock' => $outOfStock,
        ]);
    }

    public function trace(Request $request, string $sku): JsonResponse
    {
        $product = Product::where('sku', $sku)->orWhere('barcode', $sku)->firstOrFail();
        $locations = Inventory::with(['warehouse:id,code,name', 'rackSlot:id,slot_code', 'rackSlot.rackLevel.rack:id,code,name'])
            ->where('product_id', $product->id)
            ->where('quantity', '>', 0)
            ->get();

        return response()->json([
            'product' => $product,
            'locations' => $locations,
        ]);
    }
}
''',

    'PlanogramController.php': '''<?php

namespace App\\Http\\Controllers\\Api\\V1;

use App\\Http\\Controllers\\Controller;
use App\\Models\\{Planogram, PlanogramSnapshot, Warehouse};
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;

class PlanogramController extends Controller
{
    public function show(int $warehouse): JsonResponse
    {
        $planogram = Planogram::where('warehouse_id', $warehouse)
            ->with('createdBy:id,name')
            ->latest()
            ->first();

        if (!$planogram) {
            return response()->json(['data' => null, 'message' => 'Planogram not found for this warehouse'], 404);
        }

        return response()->json(['data' => $planogram]);
    }

    public function update(Request $request, int $warehouse): JsonResponse
    {
        $request->validate([
            'canvas_data' => 'required|array',
            'canvas_settings' => 'nullable|array',
            'change_summary' => 'nullable|string|max:500',
        ]);

        $warehouse = Warehouse::findOrFail($warehouse);
        $user = $request->user();

        // Get current planogram or create new
        $planogram = Planogram::where('warehouse_id', $warehouse->id)->latest()->first();

        if ($planogram) {
            // Save snapshot of current state before updating
            PlanogramSnapshot::create([
                'planogram_id' => $planogram->id,
                'version' => $planogram->version,
                'canvas_data' => $planogram->canvas_data,
                'created_by' => $user->id,
                'change_summary' => 'Auto-snapshot before edit',
                'created_at' => $planogram->updated_at,
            ]);
        }

        $newVersion = $planogram
            ? implode('.', array_map(fn($v) => $v + 1, array_reverse(explode('.', $planogram->version))))
            : '1.0';

        $planogram = Planogram::updateOrCreate(
            ['warehouse_id' => $warehouse->id],
            [
                'canvas_data' => $request->canvas_data,
                'canvas_settings' => $request->canvas_settings ?? [],
                'created_by' => $user->id,
                'version' => $newVersion,
                'change_summary' => $request->change_summary,
            ]
        );

        return response()->json(['data' => $planogram]);
    }

    public function snapshot(Request $request, int $warehouse): JsonResponse
    {
        $planogram = Planogram::where('warehouse_id', $warehouse)->latest()->firstOrFail();

        $snapshot = PlanogramSnapshot::create([
            'planogram_id' => $planogram->id,
            'version' => $planogram->version . '.snap',
            'canvas_data' => $planogram->canvas_data,
            'created_by' => $request->user()->id,
            'change_summary' => $request->change_summary ?? 'Manual snapshot',
            'created_at' => now(),
        ]);

        return response()->json(['data' => $snapshot], 201);
    }

    public function history(int $warehouse): JsonResponse
    {
        $planogram = Planogram::where('warehouse_id', $warehouse)->latest()->firstOrFail();
        $snapshots = $planogram->snapshots()->with('createdBy:id,name')->orderByDesc('created_at')->paginate(20);
        return response()->json($snapshots);
    }

    public function searchProduct(Request $request): JsonResponse
    {
        $request->validate(['q' => 'required|string|min:2']);

        $product = \\App\\Models\\Product::where('name', 'ilike', '%' . $request->q . '%')
            ->orWhere('sku', 'ilike', '%' . $request->q . '%')
            ->orWhere('barcode', 'ilike', '%' . $request->q . '%')
            ->first();

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        // Find all inventory locations for this product
        $locations = \\App\\Models\\Inventory::with([
            'warehouse:id,code,name', 'rackSlot:id,slot_code',
            'rackSlot.rackLevel.rack:id,code,pos_x,pos_y',
            'rackSlot.rackLevel.rack.zone:id,code,name',
        ])
            ->where('product_id', $product->id)
            ->where('quantity', '>', 0)
            ->get();

        return response()->json(['product' => $product, 'locations' => $locations]);
    }
}
''',

    'ReportController.php': '''<?php

namespace App\\Http\\Controllers\\Api\\V1;

use App\\Http\\Controllers\\Controller;
use App\\Models\\{Inventory, InventoryTransaction, Product, Warehouse};
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;
use PhpOffice\\PhpSpreadsheet\\Spreadsheet;
use PhpOffice\\PhpSpreadsheet\\Writer\\Xlsx;

class ReportController extends Controller
{
    public function stock(Request $request): JsonResponse
    {
        $query = Inventory::with(['product:id,sku,name,category_id', 'product.category:id,name', 'warehouse:id,code,name'])
            ->when($request->warehouse_id, fn($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->category_id, fn($q) => $q->whereHas('product', fn($pq) => $pq->where('category_id', $request->category_id)));

        $data = $query->paginate($request->get('per_page', 50));
        return response()->json($data);
    }

    public function mutations(Request $request): JsonResponse
    {
        $query = InventoryTransaction::with(['product:id,sku,name', 'warehouse:id,code,name', 'user:id,name'])
            ->when($request->warehouse_id, fn($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->product_id, fn($q) => $q->where('product_id', $request->product_id))
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->from, fn($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->to, fn($q) => $q->whereDate('created_at', '<=', $request->to))
            ->orderByDesc('created_at');

        $data = $query->paginate($request->get('per_page', 50));
        return response()->json($data);
    }

    public function aging(Request $request): JsonResponse
    {
        $days = (int) $request->get('days', 30);
        $warehouseId = $request->warehouse_id;

        // Products that haven't moved in N days
        $cutoff = now()->subDays($days);

        $aging = DB::table('inventory as i')
            ->join('products as p', 'i.product_id', '=', 'p.id')
            ->join('warehouses as w', 'i.warehouse_id', '=', 'w.id')
            ->select('p.id', 'p.sku', 'p.name', 'w.id as warehouse_id', 'w.code as warehouse_code',
                DB::raw('SUM(i.quantity) as total_qty'),
                DB::raw("MAX(it.created_at) as last_movement"),
                DB::raw("EXTRACT(DAY FROM NOW() - MAX(it.created_at)) as days_since_movement"))
            ->leftJoin('inventory_transactions as it', fn($j) => $j->on('i.product_id', '=', 'it.product_id')->on('i.warehouse_id', '=', 'it.warehouse_id'))
            ->when($warehouseId, fn($q) => $q->where('i.warehouse_id', $warehouseId))
            ->where('i.quantity', '>', 0)
            ->groupBy('p.id', 'p.sku', 'p.name', 'w.id', 'w.code')
            ->having(DB::raw("MAX(it.created_at)"), '<', $cutoff)
            ->orderByDesc('days_since_movement')
            ->paginate(50);

        return response()->json($aging);
    }

    public function expiry(Request $request): JsonResponse
    {
        $within = (int) $request->get('within_days', 30);
        $warehouseId = $request->warehouse_id;

        $data = Inventory::with(['product:id,sku,name', 'warehouse:id,code,name'])
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now(), now()->addDays($within)])
            ->orderBy('expiry_date')
            ->paginate(50);

        return response()->json($data);
    }

    public function utilization(Request $request): JsonResponse
    {
        $warehouseId = $request->warehouse_id;
        $warehouseQuery = Warehouse::query()->when($warehouseId, fn($q) => $q->where('id', $warehouseId));

        $warehouses = $warehouseQuery->with(['zones.racks.levels.slots'])->get();

        $result = $warehouses->map(fn($w) => [
            'warehouse' => ['id' => $w->id, 'code' => $w->code, 'name' => $w->name],
            'total_zones' => $w->zones->count(),
            'total_racks' => $w->zones->flatMap(fn($z) => $z->racks)->count(),
            'total_slots' => $w->zones->flatMap(fn($z) => $z->racks->flatMap(fn($r) => $r->levels->flatMap(fn($l) => $l->slots))),
            'utilization' => $w->zones->flatMap(fn($z) => $z->racks->flatMap(fn($r) => $r->levels->flatMap(fn($l) => $l->slots)))
                ->filter(fn($s) => $s->status !== 'empty')->count(),
        ]);

        return response()->json(['data' => $result]);
    }

    public function activity(Request $request): JsonResponse
    {
        $data = InventoryTransaction::with(['user:id,name', 'warehouse:id,code,name', 'product:id,sku,name'])
            ->when($request->warehouse_id, fn($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->orderByDesc('created_at')
            ->paginate($request->get('per_page', 50));

        return response()->json($data);
    }

    public function export(Request $request): JsonResponse
    {
        // Returns URL to exported file (handled asynchronously via queue)
        $type = $request->get('type', 'stock');
        $format = $request->get('format', 'xlsx');

        // TODO: Dispatch export job to queue
        return response()->json([
            'message' => 'Export job queued',
            'type' => $type, 'format' => $format,
        ]);
    }
}
''',

    'UserController.php': '''<?php

namespace App\\Http\\Controllers\\Api\\V1;

use App\\Http\\Controllers\\Controller;
use App\\Models\\User;
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\Hash;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query()->with('roles:id,name');
        if ($request->has('search')) {
            $query->where('name', 'ilike', '%' . $request->search . '%')
                  ->orWhere('email', 'ilike', '%' . $request->search . '%');
        }
        $data = $query->paginate($request->get('per_page', 15));
        return response()->json($data);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'roles' => 'array',
            'roles.*' => 'exists:roles,name',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
        ]);

        if (!empty($validated['roles'])) {
            $user->assignRole($validated['roles']);
        }

        return response()->json(['data' => $user->load('roles')], 201);
    }

    public function show(int $id): JsonResponse
    {
        $user = User::with('roles:id,name', 'permissions:id,name')->findOrFail($id);
        return response()->json(['data' => $user]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'roles' => 'array',
            'roles.*' => 'exists:roles,name',
        ]);

        $user->update($validated);

        if ($request->has('roles')) {
            $user->syncRoles($validated['roles']);
        }

        return response()->json(['data' => $user->load('roles')]);
    }

    public function destroy(int $id): JsonResponse
    {
        User::findOrFail($id)->delete();
        return response()->json(null, 204);
    }

    public function roles(): JsonResponse
    {
        return response()->json(['data' => \\Spatie\\Permission\\Models\\Role::all()]);
    }

    public function permissions(): JsonResponse
    {
        return response()->json(['data' => \\Spatie\\Permission\\Models\\Permission::all()]);
    }
}
''',

    'DocumentController.php': '''<?php

namespace App\\Http\\Controllers\\Api\\V1;

use App\\Http\\Controllers\\Controller;
use App\\Models\\Document;
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\Storage;

class DocumentController extends Controller
{
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:50',
        ]);

        $file = $request->file('file');
        $path = $file->store('documents', 'minio');

        $doc = Document::create([
            'user_id' => $request->user()->id,
            'name' => $request->name,
            'original_name' => $file->getClientOriginalName(),
            'type' => $request->type,
            'size' => $file->getSize(),
            'path' => $path,
            'disk' => 'minio',
        ]);

        return response()->json(['data' => $doc], 201);
    }

    public function show(int $id): JsonResponse
    {
        $doc = Document::findOrFail($id);
        $url = Storage::disk('minio')->url($doc->path);
        return response()->json(['data' => $doc, 'url' => $url]);
    }

    public function destroy(int $id): JsonResponse
    {
        $doc = Document::findOrFail($id);
        Storage::disk('minio')->delete($doc->path);
        $doc->delete();
        return response()->json(null, 204);
    }
}
''',

    'AuditLogController.php': '''<?php

namespace App\\Http\\Controllers\\Api\\V1;

use App\\Http\\Controllers\\Controller;
use App\\Models\\AuditLog;
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::with('user:id,name')
            ->when($request->entity_type, fn($q) => $q->where('entity_type', $request->entity_type))
            ->when($request->user_id, fn($q) => $q->where('user_id', $request->user_id))
            ->when($request->from, fn($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->to, fn($q) => $q->whereDate('created_at', '<=', $request->to))
            ->orderByDesc('created_at');

        $data = $query->paginate($request->get('per_page', 50));
        return response()->json($data);
    }
}
''',
}

services = {
    'WarehouseService.php': service('Warehouse', 'Warehouse'),
    'ProductService.php': service('Product', 'Product'),
    'PlanogramService.php': service('Planogram', 'Planogram'),
    'InventoryService.php': service('Inventory', 'Inventory'),
}

# Write all files
for fname, content in controllers.items():
    path = f'{base}/app/Http/Controllers/Api/V1/{fname}'
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)
    print(f'Controller: {fname}')

for fname, content in services.items():
    path = f'{base}/app/Services/{fname}'
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, 'w', encoding='utf-8') as f:
        f.write(content)
    print(f'Service: {fname}')

print(f'\nAll controllers and services created!')