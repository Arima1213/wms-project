<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\WarehouseController;
use App\Http\Controllers\Api\V1\ZoneController;
use App\Http\Controllers\Api\V1\RackController;
use App\Http\Controllers\Api\V1\RackSlotController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\InboundController;
use App\Http\Controllers\Api\V1\OutboundController;
use App\Http\Controllers\Api\V1\TransferController;
use App\Http\Controllers\Api\V1\StockOpnameController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\PlanogramController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DocumentController;
use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\SettingController;
use App\Http\Controllers\Api\V1\WebhookController;

// Public routes
Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

// Protected routes
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/password', [AuthController::class, 'updatePassword']);

    Route::get('/dashboard', [DashboardController::class, 'index']);
    
    Route::get('/settings', [SettingController::class, 'index']);
    Route::put('/settings', [SettingController::class, 'update']);

    Route::apiResource('users', UserController::class);
    Route::get('/roles', [UserController::class, 'roles']);
    Route::get('/permissions', [UserController::class, 'permissions']);

    Route::apiResource('warehouses', WarehouseController::class);
    Route::get('/warehouses/{id}/summary', [WarehouseController::class, 'summary']);
    Route::get('/warehouses/{id}/utilization', [WarehouseController::class, 'utilization']);

    Route::apiResource('warehouses.zones', ZoneController::class)->except(['show']);
    Route::put('/zones/{zone}/activate', [ZoneController::class, 'activate']);
    Route::put('/zones/{zone}/deactivate', [ZoneController::class, 'deactivate']);

    Route::apiResource('zones.racks', RackController::class)->except(['show']);
    Route::get('/racks/{rack}/slots', [RackController::class, 'slots']);
    Route::put('/racks/{rack}/position', [RackController::class, 'updatePosition']);

    Route::apiResource('rack-slots', RackSlotController::class);
    Route::put('/rack-slots/{slot}/assign', [RackSlotController::class, 'assignProduct']);
    Route::put('/rack-slots/{slot}/unassign', [RackSlotController::class, 'unassignProduct']);
    Route::put('/rack-slots/{slot}/reserve', [RackSlotController::class, 'reserve']);

    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('products', ProductController::class);
    Route::post('/products/import', [ProductController::class, 'import']);
    Route::get('/products/search', [ProductController::class, 'search']);
    Route::get('/products/{product}/locations', [ProductController::class, 'locations']);

    Route::get('/inventory', [InventoryController::class, 'index']);
    Route::get('/inventory/stock', [InventoryController::class, 'stock']);
    Route::get('/inventory/alerts', [InventoryController::class, 'alerts']);
    Route::get('/inventory/trace/{sku}', [InventoryController::class, 'trace']);

    Route::apiResource('inbounds', InboundController::class);
    Route::post('/inbounds/{inbound}/receive', [InboundController::class, 'receive']);
    Route::post('/inbounds/{inbound}/cancel', [InboundController::class, 'cancel']);

    Route::apiResource('outbounds', OutboundController::class);
    Route::post('/outbounds/{outbound}/pick', [OutboundController::class, 'pick']);
    Route::post('/outbounds/{outbound}/ship', [OutboundController::class, 'ship']);
    Route::post('/outbounds/{outbound}/cancel', [OutboundController::class, 'cancel']);

    Route::apiResource('transfers', TransferController::class);
    Route::post('/transfers/{transfer}/approve', [TransferController::class, 'approve']);
    Route::post('/transfers/{transfer}/reject', [TransferController::class, 'reject']);
    Route::post('/transfers/{transfer}/execute', [TransferController::class, 'execute']);

    Route::apiResource('stock-opnames', StockOpnameController::class);
    Route::post('/stock-opnames/{opname}/start', [StockOpnameController::class, 'start']);
    Route::post('/stock-opnames/{opname}/submit', [StockOpnameController::class, 'submit']);
    Route::post('/stock-opnames/{opname}/approve', [StockOpnameController::class, 'approve']);

    Route::get('/warehouses/{warehouse}/planogram', [PlanogramController::class, 'show']);
    Route::put('/warehouses/{warehouse}/planogram', [PlanogramController::class, 'update']);
    Route::post('/warehouses/{warehouse}/planogram/snapshot', [PlanogramController::class, 'snapshot']);
    Route::get('/warehouses/{warehouse}/planogram/history', [PlanogramController::class, 'history']);
    Route::get('/planogram/search', [PlanogramController::class, 'searchProduct']);

    Route::get('/reports/stock', [ReportController::class, 'stock']);
    Route::get('/reports/mutations', [ReportController::class, 'mutations']);
    Route::get('/reports/aging', [ReportController::class, 'aging']);
    Route::get('/reports/expiry', [ReportController::class, 'expiry']);
    Route::get('/reports/utilization', [ReportController::class, 'utilization']);
    Route::get('/reports/valuation', [ReportController::class, 'valuation']);
    Route::get('/reports/activity', [ReportController::class, 'activity']);
    Route::post('/reports/export', [ReportController::class, 'export']);

    Route::get('/documents', [DocumentController::class, 'index']);
Route::post('/documents/upload', [DocumentController::class, 'upload']);
    Route::get('/documents/{document}', [DocumentController::class, 'show']);
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy']);

    Route::get('/audit-logs', [AuditLogController::class, 'index']);

    Route::middleware('permission:webhook.view')->group(function () {
        Route::apiResource('webhooks', WebhookController::class);
        Route::post('/webhooks/{webhook}/test', [WebhookController::class, 'test']);
        Route::get('/webhooks/{webhook}/deliveries', [WebhookController::class, 'deliveries']);
        Route::get('/webhooks/{webhook}/deliveries/{delivery}', [WebhookController::class, 'showDelivery']);
        Route::post('/webhooks/{webhook}/deliveries/{delivery}/retry', [WebhookController::class, 'retryDelivery']);
    });
});
