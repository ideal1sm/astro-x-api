<?php


use App\Http\Api\Controllers\CatalogCategoriesController;
use App\Http\Api\Controllers\CatalogProductController;
use App\Http\Api\Controllers\CatalogProductsController;
use App\Http\Api\Controllers\CatalogSearchController;
use App\Http\Api\Controllers\GetPersonalCompilationController;
use App\Http\Api\Controllers\GetProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // ── Catalog ──────────────────────────────────────────────────────────────
    Route::get('/catalog/categories', CatalogCategoriesController::class);
    Route::get('/catalog/products', CatalogProductsController::class);
    Route::get('/catalog/products/{id}', CatalogProductController::class);
    Route::get('/catalog/search', CatalogSearchController::class);

    // ── Legacy ───────────────────────────────────────────────────────────────
    Route::get('/products/{id}', GetProductController::class);
    Route::post('/personal-compilation', GetPersonalCompilationController::class);
});
