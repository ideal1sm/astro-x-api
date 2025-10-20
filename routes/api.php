<?php


use App\Http\Api\Controllers\GetPersonalCompilationController;
use App\Http\Api\Controllers\GetProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/products/{id}', GetProductController::class);
    Route::post('/personal-compilation', GetPersonalCompilationController::class);
});
