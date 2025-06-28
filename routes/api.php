<?php

use App\Http\Controllers\Api\AdminAuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Middleware\ApiTokenAuth;
use App\Http\Middleware\VipAccess;
use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Middleware\TrackContentView;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Middleware\AuthWithToken;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DeviceController;

/*--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------*/
use App\Http\Controllers\Api\SuggestionController;



//

Route::post('/register', [DeviceController::class, 'register']);
Route::get('/db-con', function () {
    try {
        $dbconnect = DB::connection()->getPDO();
        //test
        $dbname = DB::connection()->getDatabaseName();
        echo "Connected successfully to the database. Database name is :" . $dbname;
    } catch (Exception $e) {
        echo "Error in connecting to the database" . $e->getMessage();
    }
});
//Auth routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AdminAuthController::class, 'register']);
    Route::post('/login', [AdminAuthController::class, 'login']);
    Route::post('/refresh', [AdminAuthController::class, 'refresh']);
    Route::apiResource('suggestions', SuggestionController::class); 

    // Protected routes
    Route::middleware([AuthWithToken::class])->group(function () {
        //Subscription routes
        Route::post('/create-key', [AdminController::class, 'createSubscriptionKey']);
        Route::get('/subscription-keys', [AdminController::class, 'listSubscriptionKeys']);
        Route::put('/subscriptions/{id}/deactivate', [AdminController::class, 'deactivateSubscription']);
        //Device routes
        Route::post('/devices/link', [DeviceController::class, 'linkToUser']);
        Route::post('/devices/unlink', [DeviceController::class, 'unlink']);
        Route::put('/devices/{device_id}', [DeviceController::class, 'update']);
        //
        Route::get('/devices', [AdminController::class, 'listDevices']);
        Route::get('/devices/{device_id}', [AdminController::class, 'getDevice']);
        Route::put('/devices/{device_id}/vip', [AdminController::class, 'setVipStatus']);
        Route::delete('/devices/{device_id}', [AdminController::class, 'deleteDevice']);
        //Dashboard
        Route::get('/overview', [DashboardController::class, 'overview']);
        Route::get('/users', [DashboardController::class, 'userAnalytics']);
        Route::get('/content', [DashboardController::class, 'contentAnalytics']);
        Route::get('/subscriptions', [DashboardController::class, 'subscriptionAnalytics']);
        //Route::get('/devices', [DashboardController::class, 'deviceAnalytics']);
        //
        Route::put('/contents/{id}', [AdminController::class, 'updateContent']);
        Route::delete('/contents/{id}', [AdminController::class, 'deleteContent']);
        Route::post('/contents', [AdminController::class, 'createContent']);
        Route::put('/contents/{id}', [AdminController::class, 'updateContent']);
        Route::get('/contents/{id}', [AdminController::class, 'getContentDetails'])->where('id', '[0-9]+');
        Route::get('/contents', [ContentController::class, 'listContents']);
        Route::get('/subscriptions', [AdminController::class, 'listSubscriptionKeys']);
        Route::post('/logout', [AdminAuthController::class, 'logout']);
        Route::post('/logout-all', [AdminAuthController::class, 'logoutAll']);
    });
});

// Category Routes
Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index']);
    Route::post('/', [CategoryController::class, 'store']);
    Route::get('/{id}', [CategoryController::class, 'show']);
    Route::put('/{id}', [CategoryController::class, 'update']);
    Route::delete('/{id}', [CategoryController::class, 'destroy']);
});

/*--------------------------------------------------------------------------
| Authenticated Routes (API Token Required)
|--------------------------------------------------------------------------*/
// Add these routes
Route::middleware([ApiTokenAuth::class, TrackContentView::class])->group(function () {
    Route::get('/contents/{id}', [ContentController::class, 'getContentDetails'])
        ->name('contents')->where('id', '[0-9]+');
});
Route::middleware([ApiTokenAuth::class])->group(function () {
    //device 
    Route::get('/devices/status', [DeviceController::class, 'status']);

    // Content Access
    Route::get('/contents', [ContentController::class, 'listContents']);
    Route::get('/normal-contents', [ContentController::class, 'normalContents']);
    Route::get('/contents/{id}/views', [ContentController::class, 'getContentViews']);
    // Route::get('/contents/{id}', [ContentController::class, 'getContentDetails'])->where('id', '[0-9]+');
    Route::get('/contents/tag/{tag}', [ContentController::class, 'getContentsByTag']);
    Route::get('/contents/cast/{cast}', [ContentController::class, 'getContentsByCast']);
    Route::get('/contents/category/{category}', [ContentController::class, 'getContentsByCategory']);
    Route::get('/contents/search', [ContentController::class, 'searchContents']);
    Route::get('/contents/latest', [ContentController::class, 'latestContents']);
    Route::get('/contents/home', [ContentController::class, 'getHomeContents']);
    // Subscription Management
    Route::post('/verify-key', [SubscriptionController::class, 'verifyKey']);
    Route::get('/subscription-status', [SubscriptionController::class, 'subscriptionStatus']);

    /*----------------------------------------------------------------------
    | VIP-Only Routes (VIP Subscription Required)
    |----------------------------------------------------------------------*/
    Route::middleware([VipAccess::class])->group(function () {
        Route::get('/vip-contents', [ContentController::class, 'vipContents']);
        // Add other VIP-only routes here
    });
});

/*--------------------------------------------------------------------------
| Admin Routes (Should be protected with admin auth in production)
|--------------------------------------------------------------------------*/
Route::prefix('admin')->group(function () {
    // VIP Management
    Route::post('/upgrade', [AdminController::class, 'upgradeToVip']);
    Route::post('/create-key', [AdminController::class, 'createSubscriptionKey']);

    // Content Management

    Route::post('/create-contents', [AdminController::class, 'store']);
    Route::post('/contents', [AdminController::class, 'createContent']);
    Route::put('/contents/{id}', [AdminController::class, 'updateContent']);
    Route::delete('/contents/{id}', [AdminController::class, 'deleteContent']);

    // Subscription Management
    Route::get('/subscriptions', [AdminController::class, 'listSubscriptions']);
    Route::put('/subscriptions/{id}/deactivate', [AdminController::class, 'deactivateSubscription']);
    Route::get('/subscription-keys', [AdminController::class, 'listSubscriptionKeys']);
});

/*--------------------------------------------------------------------------
| Fallback Route
|--------------------------------------------------------------------------*/
Route::fallback(function () {
    return response()->json([
        'error' => 'Endpoint not found',
        'available_endpoints' => [
            'POST /register',
            'GET /upgrade-info',
            'GET /contents',
            'GET /normal-contents',
            'GET /contents/{id}',
            'POST /verify-key',
            'GET /subscription-status',
            'GET /vip-contents (VIP)',
            'POST /admin/upgrade',
            'POST /admin/create-key',
            'POST /admin/contents',
            'PUT /admin/contents/{id}'
        ]
    ], 404);
});
