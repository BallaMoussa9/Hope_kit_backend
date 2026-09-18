<?php
use App\Actions\Fortify\CreateNewUser;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\Dashboard\AlertController;
use App\Http\Controllers\Api\Dashboard\DashboardController;
use App\Http\Controllers\Api\Dashboard\IvrController;
use App\Http\Controllers\Api\Dashboard\KitController as DashboardKitController;
use App\Http\Controllers\Api\Dashboard\ReportController;
use App\Http\Controllers\Api\Dashboard\InventoryController;
use App\Http\Controllers\Api\Dashboard\PartnerController;
use App\Http\Controllers\Api\Dashboard\CommercialDocumentController;
use App\Http\Controllers\Api\Dashboard\PaymentController;
use App\Http\Controllers\Api\Dashboard\WarehouseController;
use App\Http\Controllers\Api\Dashboard\SalesOrderController;
use App\Http\Controllers\Api\Dashboard\PurchaseOrderController;
use App\Http\Controllers\Api\Dashboard\DeliveryNoteController;
use App\Http\Controllers\Api\Dashboard\InventoryCountController;
use App\Http\Controllers\Api\Dashboard\ErpReportController;
use App\Http\Controllers\Api\Admin\AdminController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\IvrWebhookController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\Mobile\AuthController;
use App\Http\Controllers\Api\Mobile\BeneficiaryController;
use App\Http\Controllers\Api\Mobile\KitScanController;
use App\Http\Controllers\Api\Mobile\SyncController;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::middleware('permission:users.manage')->group(function () {
        Route::get('/users', [AdminController::class, 'users']); Route::post('/users', [AdminController::class, 'storeUser']); Route::put('/users/{user}', [AdminController::class, 'updateUser']);
        Route::patch('/users/{user}/active', [AdminController::class, 'setActive']); Route::post('/users/{user}/role', [AdminController::class, 'assignRole']);
    });
    Route::middleware('permission:roles.manage')->group(function () {
        Route::get('/roles', [AdminController::class, 'roles']); Route::get('/permissions', [AdminController::class, 'permissions']);
        Route::post('/roles', [AdminController::class, 'createRole']); Route::put('/roles/{role}', [AdminController::class, 'updateRole']);
    });
});

Route::withoutMiddleware([
    EnsureFrontendRequestsAreStateful::class,
])->prefix('mobile')->group(function () {

    Route::post('/login', [AuthController::class, 'login']);

        Route::post('/logout', [AuthController::class, 'logout']);

        Route::post('/logout-all', [AuthController::class, 'logoutAllDevices']);

        Route::middleware(
            'role:' .
            User::ROLE_LOGISTIQUE . ',' .
            User::ROLE_AGENT_SANTE . ',' .
            User::ROLE_COORDINATEUR . ',' .
            User::ROLE_DIRECTION
        )->group(function () {

            Route::get('/kits/{qrCode}', [KitScanController::class, 'lookup']);

            Route::post('/kits/{qrCode}/distribute', [KitScanController::class, 'distribute']);

            Route::post('/kits/{qrCode}/confirm-usage', [KitScanController::class, 'confirmUsage']);

            Route::post('/beneficiaries', [BeneficiaryController::class, 'store']);

            Route::get('/beneficiaries/search', [BeneficiaryController::class, 'search']);

            Route::post('/sync', [SyncController::class, 'push']);
        });
});

Route::patch('/ivr/webhook/{call}', [IvrWebhookController::class, 'update']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', MeController::class);
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'read']);
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll']);

    Route::middleware('permission:dashboard.view')->prefix('dashboard')->group(function () {
        Route::get('/kpi', [DashboardController::class, 'kpi']);
        Route::get('/kpi/by-region', [DashboardController::class, 'byRegion']);
        Route::get('/kpi/health-center-ranking', [DashboardController::class, 'healthCenterRanking']);

        Route::get('/kits', [DashboardKitController::class, 'index']);
        Route::get('/kits/{kit}', [DashboardKitController::class, 'show']);
        Route::get('/kits-reference/health-centers', [DashboardKitController::class, 'healthCenters'])->middleware('permission:kits.manage');
        Route::get('/kits-reference/beneficiaries', [DashboardKitController::class, 'beneficiaries'])->middleware('permission:kits.manage');
        Route::post('/kits/batch', [DashboardKitController::class, 'storeBatch'])
            ->middleware('permission:kits.manage');

        Route::get('/ivr/stats', [IvrController::class, 'stats']);
        Route::get('/ivr/calls', [IvrController::class, 'index']);

        Route::get('/reports', [ReportController::class, 'index']);
        Route::post('/reports', [ReportController::class, 'store']);
        Route::get('/reports/{report}/download', [ReportController::class, 'download']);

        Route::get('/alerts', [AlertController::class, 'index']);
        Route::patch('/alerts/{alert}/resolve', [AlertController::class, 'resolve']);

        Route::group([], function () {
            Route::get('/orders', [SalesOrderController::class, 'index'])->middleware('permission:orders.manage');
            Route::post('/orders', [SalesOrderController::class, 'store'])->middleware('permission:orders.manage');
            Route::put('/orders/{order}', [SalesOrderController::class, 'update'])->middleware('permission:orders.manage');
            Route::get('/orders/{order}', [SalesOrderController::class, 'show']);
            Route::get('/purchase-orders', [PurchaseOrderController::class, 'index'])->middleware('permission:purchases.view');
            Route::get('/purchase-orders/{order}', [PurchaseOrderController::class, 'show'])->middleware('permission:purchases.view');
            Route::post('/purchase-orders', [PurchaseOrderController::class, 'store'])->middleware('permission:purchases.manage');
            Route::put('/purchase-orders/{order}', [PurchaseOrderController::class, 'update'])->middleware('permission:purchases.manage');
            Route::post('/purchase-orders/{order}/validate', [PurchaseOrderController::class, 'validateOrder'])->middleware('permission:purchases.manage');
            Route::post('/purchase-orders/{order}/receive', [PurchaseOrderController::class, 'receive'])->middleware('permission:purchases.manage');
            Route::post('/purchase-orders/{order}/cancel', [PurchaseOrderController::class, 'cancel'])->middleware('permission:purchases.manage');
            Route::get('/purchase-orders/{order}/pdf', [PurchaseOrderController::class, 'pdf'])->middleware('permission:purchases.view');
            Route::post('/purchase-orders/{order}/email', [PurchaseOrderController::class, 'email'])->middleware('permission:purchases.manage');
            Route::get('/deliveries', [DeliveryNoteController::class, 'index'])->middleware('permission:documents.view');
            Route::post('/orders/{order}/deliveries', [DeliveryNoteController::class, 'store'])->middleware('permission:documents.manage');
            Route::get('/deliveries/{delivery}', [DeliveryNoteController::class, 'show'])->middleware('permission:documents.view');
            Route::get('/deliveries/{delivery}/pdf', [DeliveryNoteController::class, 'pdf'])->middleware('permission:documents.view');
            Route::post('/deliveries/{delivery}/email', [DeliveryNoteController::class, 'email'])->middleware('permission:documents.manage');
            Route::get('/inventory-counts', [InventoryCountController::class, 'index'])->middleware('permission:stock.view');
            Route::post('/inventory-counts', [InventoryCountController::class, 'store'])->middleware('permission:stock.manage');
            Route::post('/inventory-counts/{count}/validate', [InventoryCountController::class, 'validateCount'])->middleware('permission:stock.manage');
            Route::get('/erp-reports', [ErpReportController::class, 'index'])->middleware('permission:reports.view');
            Route::get('/erp-reports/print', [ErpReportController::class, 'print'])->middleware('permission:reports.view');
            Route::post('/erp-reports/email', [ErpReportController::class, 'email'])->middleware('permission:reports.view');
            Route::post('/orders/{order}/validate', [SalesOrderController::class, 'validateOrder'])->middleware('permission:orders.manage');
            Route::post('/orders/{order}/cancel', [SalesOrderController::class, 'cancel'])->middleware('permission:orders.manage');
            Route::patch('/orders/{order}/status', [SalesOrderController::class, 'setStatus'])->middleware('permission:orders.manage');
            Route::post('/orders/{order}/invoice', [SalesOrderController::class, 'invoice'])->middleware('permission:documents.manage');
            Route::get('/orders/{order}/pdf', [SalesOrderController::class, 'pdf'])->middleware('permission:orders.manage');
            Route::post('/orders/{order}/email', [SalesOrderController::class, 'email'])->middleware('permission:orders.manage');
            Route::get('/inventory', [InventoryController::class, 'index'])->middleware('permission:stock.view');
            Route::get('/inventory/dashboard', [InventoryController::class, 'dashboard'])->middleware('permission:stock.view');
            Route::post('/inventory', [InventoryController::class, 'store'])->middleware('permission:products.manage');
            Route::put('/inventory/{product}', [InventoryController::class, 'update'])->middleware('permission:products.manage');
            Route::post('/inventory/{product}/movement', [InventoryController::class, 'movement'])->middleware('permission:stock.manage');
            Route::get('/inventory/movements/list', [InventoryController::class, 'movements'])->middleware('permission:stock.view');
            Route::get('/warehouses', [WarehouseController::class, 'index'])->middleware('permission:stock.view');
            Route::post('/warehouses', [WarehouseController::class, 'store'])->middleware('permission:warehouses.manage');
            Route::put('/warehouses/{warehouse}', [WarehouseController::class, 'update'])->middleware('permission:warehouses.manage');
            Route::get('/warehouses/{warehouse}/stock', [WarehouseController::class, 'stock'])->middleware('permission:stock.view');
            Route::post('/warehouses/transfer', [WarehouseController::class, 'transfer'])->middleware('permission:stock.manage');
            Route::get('/suppliers', [PartnerController::class, 'suppliers'])->middleware('permission:customers.view');
            Route::post('/suppliers', [PartnerController::class, 'storeSupplier'])->middleware('permission:customers.manage');
            Route::put('/suppliers/{supplier}', [PartnerController::class, 'updateSupplier'])->middleware('permission:customers.manage');
            Route::get('/customers', [PartnerController::class, 'customers'])->middleware('permission:customers.view');
            Route::post('/customers', [PartnerController::class, 'storeCustomer'])->middleware('permission:customers.manage');
            Route::put('/customers/{customer}', [PartnerController::class, 'updateCustomer'])->middleware('permission:customers.manage');
            Route::get('/documents', [CommercialDocumentController::class, 'index'])->middleware('permission:documents.view');
            Route::post('/documents', [CommercialDocumentController::class, 'store'])->middleware('permission:documents.manage');
            Route::get('/documents/{document}', [CommercialDocumentController::class, 'show'])->middleware('permission:documents.view');
            Route::post('/documents/{document}/convert', [CommercialDocumentController::class, 'convert'])->middleware('permission:documents.manage');
            Route::patch('/documents/{document}/status', [CommercialDocumentController::class, 'setStatus'])->middleware('permission:documents.manage');
            Route::get('/documents/{document}/credit-note/preview', [CommercialDocumentController::class, 'creditNotePreview'])->middleware('permission:documents.view');
            Route::post('/documents/{document}/credit-note', [CommercialDocumentController::class, 'createCreditNote'])->middleware('permission:documents.manage');
            Route::get('/documents/{document}/print', [CommercialDocumentController::class, 'print'])->middleware('permission:documents.view');
            Route::get('/documents/{document}/pdf', [CommercialDocumentController::class, 'pdf'])->middleware('permission:documents.view');
            Route::post('/documents/{document}/email', [CommercialDocumentController::class, 'email'])->middleware('permission:documents.manage');
            Route::get('/payments', [PaymentController::class, 'index'])->middleware('permission:payments.view');
            Route::post('/documents/{document}/payments', [PaymentController::class, 'store'])->middleware('permission:payments.manage');
            Route::get('/payments/{payment}/pdf', [PaymentController::class, 'pdf'])->middleware('permission:payments.view');
            Route::post('/payments/{payment}/email', [PaymentController::class, 'email'])->middleware('permission:payments.manage');
            Route::patch('/payments/{payment}/status', [PaymentController::class, 'setStatus'])->middleware('permission:payments.manage');
        });
    });


});
Route::post('/register', function (Request $request, CreateNewUser $creator) {
    $user = $creator->create($request->all());

    $token = $user->createToken('HOPE Web')->plainTextToken;

    return response()->json([
        'message' => 'Compte créé avec succès.',
        'user' => $user,
        'token' => $token,
    ], 201);
});
