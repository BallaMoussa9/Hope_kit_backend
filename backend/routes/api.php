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
use App\Http\Controllers\Api\IvrWebhookController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\Mobile\AuthController;
use App\Http\Controllers\Api\Mobile\BeneficiaryController;
use App\Http\Controllers\Api\Mobile\KitScanController;
use App\Http\Controllers\Api\Mobile\SyncController;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

Route::withoutMiddleware([
    EnsureFrontendRequestsAreStateful::class,
])->prefix('mobile')->group(function () {

    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {

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
});

Route::patch('/ivr/webhook/{call}', [IvrWebhookController::class, 'update']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', MeController::class);

    Route::middleware('role:direction,'.User::ROLE_COORDINATEUR.','.User::ROLE_LOGISTIQUE)->prefix('dashboard')->group(function () {
        Route::get('/kpi', [DashboardController::class, 'kpi']);
        Route::get('/kpi/by-region', [DashboardController::class, 'byRegion']);
        Route::get('/kpi/health-center-ranking', [DashboardController::class, 'healthCenterRanking']);

        Route::get('/kits', [DashboardKitController::class, 'index']);
        Route::get('/kits/{kit}', [DashboardKitController::class, 'show']);
        Route::post('/kits/batch', [DashboardKitController::class, 'storeBatch'])
            ->middleware('role:'.User::ROLE_DIRECTION);

        Route::get('/ivr/stats', [IvrController::class, 'stats']);
        Route::get('/ivr/calls', [IvrController::class, 'index']);

        Route::get('/reports', [ReportController::class, 'index']);
        Route::post('/reports', [ReportController::class, 'store']);
        Route::get('/reports/{report}/download', [ReportController::class, 'download']);

        Route::get('/alerts', [AlertController::class, 'index']);
        Route::patch('/alerts/{alert}/resolve', [AlertController::class, 'resolve']);

        Route::middleware('role:'.User::ROLE_DIRECTION.','.User::ROLE_COORDINATEUR.','.User::ROLE_LOGISTIQUE)->group(function () {
            Route::get('/inventory', [InventoryController::class, 'index']);
            Route::get('/inventory/dashboard', [InventoryController::class, 'dashboard']);
            Route::post('/inventory', [InventoryController::class, 'store']);
            Route::put('/inventory/{product}', [InventoryController::class, 'update']);
            Route::post('/inventory/{product}/movement', [InventoryController::class, 'movement']);
            Route::get('/inventory/movements/list', [InventoryController::class, 'movements']);
            Route::get('/suppliers', [PartnerController::class, 'suppliers']);
            Route::post('/suppliers', [PartnerController::class, 'storeSupplier']);
            Route::put('/suppliers/{supplier}', [PartnerController::class, 'updateSupplier']);
            Route::get('/customers', [PartnerController::class, 'customers']);
            Route::post('/customers', [PartnerController::class, 'storeCustomer']);
            Route::put('/customers/{customer}', [PartnerController::class, 'updateCustomer']);
            Route::get('/documents', [CommercialDocumentController::class, 'index']);
            Route::post('/documents', [CommercialDocumentController::class, 'store']);
            Route::get('/documents/{document}', [CommercialDocumentController::class, 'show']);
            Route::post('/documents/{document}/convert', [CommercialDocumentController::class, 'convert']);
            Route::post('/documents/{document}/payment', [CommercialDocumentController::class, 'payment']);
            Route::get('/documents/{document}/print', [CommercialDocumentController::class, 'print']);
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
