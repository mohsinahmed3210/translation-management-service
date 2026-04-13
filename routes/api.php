<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\TranslationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('translations', TranslationController::class);
    Route::get('export/{locale}', ExportController::class);
});
