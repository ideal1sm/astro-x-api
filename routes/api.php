<?php


use App\Http\Api\Controllers\AuthController;
use App\Http\Api\Controllers\CatalogCategoriesController;
use App\Http\Api\Controllers\OrderController;
use App\Http\Api\Controllers\CatalogProductController;
use App\Http\Api\Controllers\CatalogProductsController;
use App\Http\Api\Controllers\CatalogSearchController;
use App\Http\Api\Controllers\GetPersonalCompilationController;
use App\Http\Api\Controllers\GetProductController;
use App\Http\Api\Controllers\HomeController;
use App\Http\Api\Controllers\ProfileController;
use App\Http\Api\Controllers\ShopCatalogCategoriesController;
use App\Http\Api\Controllers\ShopCatalogProductController;
use App\Http\Api\Controllers\ShopCatalogProductsController;
use App\Http\Api\Controllers\ShopCatalogSearchController;
use App\Http\Api\Controllers\ShopOrderController;
use App\Http\Api\Controllers\UserAddressController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // ── Home ─────────────────────────────────────────────────────────────────
    Route::get('/home', HomeController::class);

    // ── Auth (public) ─────────────────────────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login',    [AuthController::class, 'login'])->middleware('throttle:10,1');
        Route::post('/email/confirm',      [AuthController::class, 'confirmEmail']);
        Route::post('/password/forgot',    [AuthController::class, 'forgotPassword']);
        Route::post('/password/reset',     [AuthController::class, 'resetPassword']);

        // Auth (protected)
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout',        [AuthController::class, 'logout']);
            Route::post('/email/resend',  [AuthController::class, 'resendEmail']);
        });
    });

    // ── Profile + Addresses (protected) ──────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me',    [ProfileController::class, 'show']);
        Route::patch('/me',  [ProfileController::class, 'update']);

        Route::get('/me/addresses',          [UserAddressController::class, 'index']);
        Route::post('/me/addresses',         [UserAddressController::class, 'store']);
        Route::patch('/me/addresses/{id}',   [UserAddressController::class, 'update']);
        Route::delete('/me/addresses/{id}',  [UserAddressController::class, 'destroy']);
    });

    // ── Orders (protected) ────────────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/orders',      [OrderController::class, 'index']);
        Route::post('/orders',     [OrderController::class, 'store']);
        Route::get('/orders/{id}', [OrderController::class, 'show']);
    });

    // ── Catalog ──────────────────────────────────────────────────────────────
    Route::get('/catalog/categories', CatalogCategoriesController::class);
    Route::get('/catalog/products', CatalogProductsController::class);
    Route::get('/catalog/products/{id}', CatalogProductController::class);
    Route::get('/catalog/search', CatalogSearchController::class);

    // ── Shop catalog (Мёд) ───────────────────────────────────────────────────
    Route::get('/shop/catalog/categories', ShopCatalogCategoriesController::class);
    Route::get('/shop/catalog/products', ShopCatalogProductsController::class);
    Route::get('/shop/catalog/products/{id}', ShopCatalogProductController::class);
    Route::get('/shop/catalog/search', ShopCatalogSearchController::class);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/shop/orders',      [ShopOrderController::class, 'index']);
        Route::post('/shop/orders',     [ShopOrderController::class, 'store']);
        Route::get('/shop/orders/{id}', [ShopOrderController::class, 'show']);
    });

    // ── Legacy ───────────────────────────────────────────────────────────────
    Route::get('/products/{id}', GetProductController::class);
    Route::post('/personal-compilation', GetPersonalCompilationController::class);
});
