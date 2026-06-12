<?php

use App\Http\Controllers\AddonController;
use App\Http\Controllers\Api\DailySalesController;
use App\Http\Controllers\Api\PaymentProcessorController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CapitalController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DatabaseBackupController;
use App\Http\Controllers\GoogleLoginController;
use App\Http\Controllers\IngredientController;
use App\Http\Controllers\IngredientLogController;
use App\Http\Controllers\IngredientStockController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ItemLogController;
use App\Http\Controllers\ItemStockController;
use App\Http\Controllers\KitchenController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\OperationalController;
use App\Http\Controllers\OrderManagementController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductLogController;
use App\Http\Controllers\ProductStockController;
use App\Http\Controllers\PromoController;
use App\Http\Controllers\PromoFreebieController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\Public\TableController as PublicTableController;
use App\Http\Controllers\RecycleBinController;
use App\Http\Controllers\RemoteCameraController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\System\ActivityLogController;
use App\Http\Controllers\System\PermissionController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\TableSeatController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\Artisan;

Route::get('/fix-route', function() {
    Artisan::call('route:clear');
    Artisan::call('config:clear');
    Artisan::call('view:clear');
    return "Route, Config, and View Cache Cleared!";
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('/kitchen', [KitchenController::class, 'index'])->name('kitchen.index');
    Route::post('/kitchen/{order}/start', [KitchenController::class, 'start'])->name('kitchen.start');
    Route::post('/kitchen/{order}/complete', [KitchenController::class, 'complete'])->name('kitchen.complete');

    Route::resource('orders', OrderManagementController::class);

    Route::resource('menu', MenuController::class);
    Route::resource('packages', PromoController::class);
    Route::resource('promotions', PromotionController::class);

    Route::prefix('master')->name('master.')->group(function () {
        Route::resource('categories', CategoryController::class);
        Route::resource('addons', AddonController::class);
        Route::resource('freebies', PromoFreebieController::class);
        Route::resource('users', UserController::class);
        Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::resource('branches', BranchController::class);
        Route::resource('tables', TableSeatController::class);
        Route::resource('capitals', CapitalController::class);
    });

    Route::prefix('product')->name('product.')->group(function () {
        Route::resource('data-products', ProductController::class);
        Route::post('product-stocks/add', [ProductStockController::class, 'addStock'])->name('product-stocks.add');
        Route::post('product-stocks/adjust', [ProductStockController::class, 'adjustStock'])->name('product-stocks.adjust');
        Route::resource('product-stocks', ProductStockController::class);
        Route::resource('product-logs', ProductLogController::class);
    });

    Route::prefix('ingredient')->name('ingredient.')->group(function () {
        Route::resource('data-ingredients', IngredientController::class);
        Route::post('ingredient-stocks/add', [IngredientStockController::class, 'addStock'])->name('ingredient-stocks.add');
        Route::post('ingredient-stocks/adjust', [IngredientStockController::class, 'adjustStock'])->name('ingredient-stocks.adjust');
        Route::post('ingredient-stocks/request', [IngredientStockController::class, 'requestIngredient'])->name('ingredient-stocks.request');
        Route::post('ingredient-stocks/request/{id}/approve', [IngredientStockController::class, 'approveRequest'])->name('ingredient-stocks.request.approve');
        Route::post('ingredient-stocks/request/{id}/reject', [IngredientStockController::class, 'rejectRequest'])->name('ingredient-stocks.request.reject');
        Route::post('ingredient-stocks/dispose', [IngredientStockController::class, 'disposeIngredient'])->name('ingredient-stocks.dispose');
        Route::post('ingredient-stocks/dispose/{id}/approve', [IngredientStockController::class, 'approveDisposal'])->name('ingredient-stocks.dispose.approve');
        Route::post('ingredient-stocks/dispose/{id}/reject', [IngredientStockController::class, 'rejectDisposal'])->name('ingredient-stocks.dispose.reject');
        Route::resource('ingredient-stocks', IngredientStockController::class);
        Route::resource('ingredient-logs', IngredientLogController::class);
    });

    Route::prefix('item')->name('item.')->group(function () {
        Route::resource('data-items', ItemController::class);
        Route::post('item-stocks/add', [ItemStockController::class, 'addStock'])->name('item-stocks.add');
        Route::post('item-stocks/adjust', [ItemStockController::class, 'adjustStock'])->name('item-stocks.adjust');
        Route::post('item-stocks/{stockid}/request', [ItemStockController::class, 'requestItem'])->name('item-stocks.request');
        Route::post('item-stocks/{stockid}/dispose', [ItemStockController::class, 'disposeItem'])->name('item-stocks.dispose');
        Route::resource('item-stocks', ItemStockController::class);
        Route::resource('item-logs', ItemLogController::class);
    });

    Route::prefix('finance')->name('finance.')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports');
        Route::get('/reports/financial', [ReportController::class, 'financialReports'])->name('reports.financial');
        Route::get('/reports/financial/excel', [ReportController::class, 'exportFinancialExcel'])->name('reports.financial.excel');
        Route::get('/reports/financial/pdf', [ReportController::class, 'exportFinancialPdf'])->name('reports.financial.pdf');
        Route::get('/reports/print', [ReportController::class, 'printReport'])->name('reports.print');
        Route::resource('salaries', PayrollController::class);
        Route::resource('operationals', OperationalController::class);
    });

    Route::prefix('system')->name('system.')->group(function () {
        Route::get('/recycle-bin', [RecycleBinController::class, 'index'])->name('recycle-bin.index');
        Route::post('/recycle-bin/{type}/{id}/restore', [RecycleBinController::class, 'restore'])->name('recycle-bin.restore');
        Route::post('/recycle-bin/{type}/{id}/force-delete', [RecycleBinController::class, 'forceDelete'])->name('recycle-bin.force-delete');
        Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('activity-log');
        Route::get('/backup-database', [DatabaseBackupController::class, 'index'])->name('backup.database');
        Route::get('/backup-database/export', [DatabaseBackupController::class, 'export'])->name('backup.database.export');
        Route::post('/backup-database/import', [DatabaseBackupController::class, 'import'])->name('backup.database.import');
        Route::get('/settings', [SystemController::class, 'index'])->name('settings');
        Route::put('/settings', [SystemController::class, 'update'])->name('settings.update');
        Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions');
        Route::post('/permissions', [PermissionController::class, 'update'])->name('permissions.update');
    });

    Route::get('/profile', function () {
        return Inertia::render('Profile');
    })->name('profile');
    
    Route::post('/profile/face', [UserController::class, 'updateFaceData'])->name('profile.face.update');
    Route::post('/api/face-approval/verify', [\App\Http\Controllers\Api\FaceApprovalController::class, 'verify'])->name('api.face-approval.verify');
    Route::post('/api/login/check-face', [\App\Http\Controllers\Api\FaceApprovalController::class, 'checkFace'])->name('api.login.check-face');
    Route::post('/api/login/verify-face', [\App\Http\Controllers\Api\FaceApprovalController::class, 'verifyLoginFace'])->name('api.login.verify-face');

    Route::prefix('api/cashier')->name('api.cashier.')->group(function () {
        Route::post('/pay', [PaymentProcessorController::class, 'process'])->name('pay');
        Route::get('/receipt/{order}', [PaymentProcessorController::class, 'receipt'])->name('receipt');
        Route::get('/daily-sales', [DailySalesController::class, 'index'])->name('daily-sales');
    });

    // Remote Camera Routes
    Route::post('/remote-camera/request', [RemoteCameraController::class, 'request'])->name('remote-camera.request');
    Route::post('/remote-camera/scanned', [RemoteCameraController::class, 'scanned'])->name('remote-camera.scanned');
});

Route::post('/language/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'id'])) {
        session(['locale' => $locale]);
    }

    return back();
})->name('language.switch');

// Auth Routes (Guest)
Route::middleware('guest')->group(function () {
    Route::get('/login/google', [GoogleLoginController::class, 'redirect'])->name('login.google');
    Route::get('/login/google/callback', [GoogleLoginController::class, 'callback'])->name('login.google.callback');
    Route::get('/login/otp', [OtpController::class, 'show'])->name('otp.login');
    Route::post('/login/otp/send', [OtpController::class, 'send'])->name('otp.send');
    Route::post('/login/otp/verify', [OtpController::class, 'verify'])->name('otp.verify');
});

// Public Table Routes
Route::prefix('table/{barcode}')->name('public.table.')->group(function () {
    Route::get('/', [PublicTableController::class, 'welcome'])->name('welcome');
    Route::get('/menu', [PublicTableController::class, 'menu'])->name('menu');
    Route::post('/checkout', [PublicTableController::class, 'processCheckout'])->name('processCheckout');
    Route::get('/payment/{order}', [PublicTableController::class, 'payment'])->name('payment');
    Route::get('/waiting/{order}', [PublicTableController::class, 'waiting'])->name('waiting');
    Route::get('/status/{order}', [PublicTableController::class, 'checkStatus'])->name('checkStatus');
    Route::post('/cancel/{order}', [PublicTableController::class, 'cancelOrder'])->name('cancel');
});

// Guest Scanner Routes
Route::get('/scan', function () {
    return inertia('Public/GuestScanner');
})->name('guest-scanner');

// Face Login APIs
Route::post('/api/login/check-face', [\App\Http\Controllers\Api\FaceApprovalController::class, 'checkFace'])->name('api.login.check-face');
Route::post('/api/login/verify-face', [\App\Http\Controllers\Api\FaceApprovalController::class, 'verifyLoginFace'])->name('api.login.verify-face');

// Remote Staff Scanner
Route::get('/mobile-scanner', [\App\Http\Controllers\RemoteScannerController::class, 'index'])->name('staff.mobile-scanner');
Route::post('/api/staff-scan/broadcast', [\App\Http\Controllers\RemoteScannerController::class, 'broadcast'])->name('api.staff-scan.broadcast');

Route::get('/api/remote-camera/network', [RemoteCameraController::class, 'getNetworkInfo'])->name('api.remote-camera.network');
Route::post('/api/remote-camera/ping', [RemoteCameraController::class, 'ping'])->name('api.remote-camera.ping');
Route::post('/api/remote-camera/guest-scanned', [RemoteCameraController::class, 'guestScanned'])->name('api.remote-camera.guest-scanned');
Route::post('/api/remote-camera/guest-request', [RemoteCameraController::class, 'guestRequest'])->name('api.remote-camera.guest-request');
