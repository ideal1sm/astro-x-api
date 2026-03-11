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

    // ── Legacy ───────────────────────────────────────────────────────────────
    Route::get('/products/{id}', GetProductController::class);
    Route::post('/personal-compilation', GetPersonalCompilationController::class);
});
