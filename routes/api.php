<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GateController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\AccessLogController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('gate')->group(function () {
        Route::post('/open', [GateController::class, 'open']);
        Route::post('/close', [GateController::class, 'close']);
    });

    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::patch('/', [ProfileController::class, 'update']);
    });

    Route::get('/access-logs', [AccessLogController::class, 'index']);
	Route::get('/access-logs/latest-successful', [AccessLogController::class, 'latestSuccessful']);
});

Route::post('/test', [TestController::class, 'index']);